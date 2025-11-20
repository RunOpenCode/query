<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Replica;

use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\IsolationException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;

/**
 * Replica middleware supports using database replica for executing queries.
 */
final readonly class ReplicaMiddleware implements MiddlewareInterface
{
    /**
     * Create new instance of replica middleware.
     *
     * @param non-empty-string                 $primary  Primary connection name for which replicas can be used.
     * @param non-empty-list<non-empty-string> $replicas Adapter connection names of database replicas.
     * @param AdapterRegistry                  $adapters Registered connection adapters.
     * @param FallbackStrategy                 $fallback Default fallback strategy to use, if none provided with configuration.
     * @param bool                             $disabled Flag denoting if replicas are disabled (useful for development/testing environment).
     */
    public function __construct(
        private string           $primary,
        private array            $replicas,
        private AdapterRegistry  $adapters,
        private FallbackStrategy $fallback = FallbackStrategy::Primary,
        private bool             $disabled = false,
    ) {
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
        $configuration = $context->peak(Replica::class);

        if (null === $configuration) {
            return $next($query, $context);
        }

        $options    = $context->peak(OptionsInterface::class);
        $primary    = $this->adapters->get($configuration->connection);

        if ($primary !== $this->adapters->get($options?->connection)) {
            return $next($query, $context);
        }

        // Mark configuration as used.
        $context->require($configuration);

        if ($this->disabled) {
            return $next($query, $context);
        }

        $connections = $this->connections($configuration);
        $exception   = null;

        foreach ($connections as $current) {
            // Fork context.
            $fork = null !== $options ? $context->replace(
                $options,
                $options->withConnection($current)
            ) : $context->append(
                // @phpstan-ignore-next-line
                $primary->defaults(OptionsInterface::class)->withConnection($current)
            );
            
            try {
                return $next($query, $fork);
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
    public function statement(string $statement, ContextInterface $context, callable $next): int
    {
        $configuration = $context->require(Replica::class);

        if (null === $configuration) {
            return $next($statement, $context);
        }

        throw new LogicException(\sprintf(
            'Replica must not be used for executing statement "%s", only queries.',
            $statement,
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

        return match ($configuration->fallback ?? $this->fallback) {
            FallbackStrategy::Any => [...$replicas, $this->primary],
            FallbackStrategy::None => [$replicas[0]],
            FallbackStrategy::Primary => [$replicas[0], $this->primary],
            FallbackStrategy::Replicas => $replicas,
        };
    }
}