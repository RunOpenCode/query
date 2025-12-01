<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Replica;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\Catcher;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\IsolationException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;

/**
 * Replica middleware supports using database replica for executing queries.
 */
final readonly class ReplicaMiddleware implements QueryMiddlewareInterface
{
    private Catcher $catcher;

    /**
     * Create new instance of replica middleware.
     *
     * @param non-empty-string                    $primary  Primary connection name for which replicas can be used.
     * @param non-empty-list<non-empty-string>    $replicas Adapter connection names of database replicas.
     * @param AdapterRegistry                     $adapters Registered adapters.
     * @param FallbackStrategy                    $fallback Default fallback strategy to use, if none provided with configuration.
     * @param bool                                $disabled Flag denoting if replicas are disabled (useful for development/testing environment).
     * @param list<class-string<\Exception>>|null $catch    Exceptions on which replica should fallback to other connections. If not provided, defaults will be used.
     */
    public function __construct(
        private string           $primary,
        private array            $replicas,
        private AdapterRegistry  $adapters,
        private FallbackStrategy $fallback = FallbackStrategy::Primary,
        private bool             $disabled = false,
        ?array                   $catch = null,
    ) {
        \assert(!\in_array($this->primary, $this->replicas, true), new LogicException(\sprintf(
            'Primary connection "%s" is configured as replica connection.',
            $this->primary,
        )));

        $this->catcher = new Catcher(!empty($catch) ? $catch : [
            DeadlockException::class,
            ConnectionException::class,
            IsolationException::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        $configuration = $context->peak(Replica::class);

        if (null === $configuration) {
            return $next($query, $context);
        }

        $connection = $configuration->connection ?? $this->adapters->default;

        if ($connection !== $context->execution->connection) {
            return $next($query, $context);
        }

        // Mark configuration as used.
        $context->require($configuration);

        if ($this->disabled) {
            return $next($query, $context);
        }

        $connections = $this->connections($configuration);
        $first       = null;
        $catcher     = $configuration->catch ? new Catcher($configuration->catch) : $this->catcher;

        foreach ($connections as $current) {
            try {
                $fork = $context->withExecution(
                    $context
                        ->execution
                        ->withConnection($current)
                );

                return $next($query, $fork);
            } catch (\Exception $exception) {
                $caught = $catcher->catch($exception);
                $first  = $first ?? $caught;
            }
        }

        throw $first;
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
