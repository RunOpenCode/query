<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Registry of all executor adapters.
 *
 * @internal
 */
final readonly class AdapterRegistry
{
    /**
     * @var AdapterInterface[]
     */
    private array $registry;

    /**
     * @param iterable<non-empty-string, AdapterInterface> $executors
     */
    public function __construct(iterable $executors = [])
    {
        $registry = [];

        foreach ($executors as $connection => $executor) {
            // Ensure that executor for connection is not already registered.
            \assert(!isset($registry[$connection]), new LogicException(\sprintf(
                'Executor for connection name "%s" is already registered in registry.',
                $connection
            )));

            $registry[$connection] = $executor;
        }

        $this->registry = $registry;
    }

    /**
     * Get executor by connection name.
     *
     * If connection name is not provided, first registered adapter will be returned.
     *
     * @param non-empty-string|null $connection Connection name used by executor, or default if not provided.
     *
     * @return AdapterInterface
     *
     * @throws RuntimeException If executor does not exists.
     */
    public function get(?string $connection = null): AdapterInterface
    {
        if (null === $connection) {
            return \array_values($this->registry)[0];
        }

        return $this->registry[$connection] ?? throw new RuntimeException(\sprintf(
            'Executor for connection name "%s" does not exists.',
            $connection
        ));
    }
}
