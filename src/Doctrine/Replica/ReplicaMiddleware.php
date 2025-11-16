<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Replica;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Options;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\IsolationException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Middleware\Context;

/**
 * Replica middleware will execute query against database replica instead of primary database.
 */
final readonly class ReplicaMiddleware implements MiddlewareInterface
{
    /**
     * @var non-empty-string
     */
    private string $primary;

    /**
     * @var non-empty-list<non-empty-string>
     */
    private array $replicas;

    /**
     * Create new instance of Doctrine replica middleware.
     *
     * @param non-empty-string|null                             $primary  Primary connection name for which replicas can be used (or null for default connection).
     * @param non-empty-list<non-empty-string>|non-empty-string $replicas Connection name or names of database replicas.
     * @param AdapterRegistry                                   $adapters Registered connection adapters.
     * @param bool                                              $disabled Flag denoting if replicas are disabled (useful for development/testing environment).
     */
    public function __construct(
        ?string                 $primary,
        array|string            $replicas,
        private AdapterRegistry $adapters,
        private bool            $disabled = false,
    ) {
        $this->primary  = $primary ?? $this->adapters->get()->name;
        $this->replicas = (array)$replicas;

        \assert(!\in_array($this->primary, $this->replicas, true), new LogicException(\sprintf(
            'Primary connection "%s" is configured as replica connection.',
            $this->primary,
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, ContextInterface $context, callable $next): ResultInterface
    {
        $options    = $context->peak(Options::class);
        $connection = $this->adapters->get($options?->connection)->name;

        // Query does not uses this replica middleware.
        if ($connection !== $this->primary) {
            return $next($query, $context);
        }

        $configurations = $context->filter(
            fn(Replica $configuration): bool => $this->adapters->get($configuration->connection)->name === $connection,
            Replica::class
        );

        // Usage of replica is not requested.
        if (0 === \count($configurations)) {
            return $next($query, $context);
        }

        // Invalid configuration.
        if (1 < \count($configurations)) {
            throw new LogicException(\sprintf(
                'There are two replica configurations for same connection name "%s".',
                $connection,
            ));
        }

        /**
         * Mark configuration as used.
         *
         * @var Replica $configuration
         */
        $configuration = $context->require($configurations[0]);

        // Replication is disabled.
        if ($this->disabled) {
            return $next($query, $context);
        }

        $connections = $this->connections($configuration);
        $exception   = null;

        foreach ($connections as $connection) {
            try {
                return $next(
                    $query,
                    $this->replicaContext($connection, $options, $context)
                );
            } catch (ConnectionException|DeadlockException|IsolationException $e) {
                // Store first exception to throw if everything fails.
                $exception = $exception ?? $e;
            }
        }

        throw $exception;
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ContextInterface $context, callable $next): int
    {
        $configuration = $context->require(Replica::class);

        if (null === $configuration) {
            return $next($query, $context);
        }

        throw new LogicException(\sprintf(
            'Replica must not be used for executing statement "%s", only queries.',
            $query,
        ));
    }

    /**
     * Get connections against which query should be executed.
     *
     * @param Replica $configuration Current replica configuration.
     *
     * @return non-empty-list<non-empty-string> List of connections to use.
     */
    private function connections(Replica $configuration): array
    {
        $replicas = $this->replicas;

        // Load balance replicas using round-robin.
        if (1 < \count($replicas)) {
            \shuffle($replicas);
        }

        return match ($configuration->fallback) {
            FallbackStrategy::Any => [...$replicas, $this->primary],
            FallbackStrategy::None => [$replicas[0]],
            FallbackStrategy::Primary => [$replicas[0], $this->primary],
            FallbackStrategy::Replicas => $replicas,
        };
    }

    /**
     * Create execution context for replica.
     *
     * @param non-empty-string $connection Connection to use.
     * @param Options|null     $options    Primary execution options.
     * @param ContextInterface $context    Current execution context.
     *
     * @return ContextInterface Context for replica execution.
     */
    private function replicaContext(string $connection, ?Options $options, ContextInterface $context): ContextInterface
    {
        $configurations    = \array_filter($context->configurations, static fn(object $current): bool => !$current instanceof Options);
        $replicatedOptions = new Options(
            connection: $connection,
            isolation: $options?->isolation,
            scope: $options?->scope,
            tags: $options?->tags,
        );

        $replicaContext = new Context(
            configurations: [
                ...$configurations,
                $replicatedOptions,
            ],
            transaction: $context->transaction,
        );

        foreach ($context->used() as $used) {
            $replicaContext->require($used);
        }

        return $replicaContext;
    }
}