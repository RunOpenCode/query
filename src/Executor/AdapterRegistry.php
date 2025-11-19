<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Registry of all executor adapters.
 */
final readonly class AdapterRegistry
{
    /**
     * @var AdapterInterface[]
     */
    private array $registry;

    /**
     * @param iterable<AdapterInterface> $adapters
     */
    public function __construct(iterable $adapters = [])
    {
        $registry = [];

        foreach ($adapters as $adapter) {
            // Ensure that executor for connection is not already registered.
            \assert(!isset($registry[$adapter->name]), new LogicException(\sprintf(
                'Executor adapter with same connection name "%s" is already registered in registry.',
                $adapter->name,
            )));

            $registry[$adapter->name] = $adapter;
        }

        $this->registry = $registry;
    }

    /**
     * Get executor adapter by connection name.
     *
     * If connection name is not provided, first registered adapter will be returned.
     *
     * @param non-empty-string|null $connection Connection name used by executor adapter, or default if not provided.
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
            'Executor adapter for connection name "%s" does not exists.',
            $connection
        ));
    }
}
