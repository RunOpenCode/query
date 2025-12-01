<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Registry of all executor adapters.
 */
final readonly class AdapterRegistry
{
    /**
     * Default connection to use.
     *
     * @var non-empty-string
     */
    public string $default;

    /**
     * @var AdapterInterface[]
     */
    private array $registry;

    /**
     * @param iterable<AdapterInterface> $adapters
     * @param non-empty-string|null      $default
     */
    public function __construct(
        iterable $adapters = [],
        ?string  $default = null,
    ) {
        $registry = [];

        foreach ($adapters as $adapter) {
            $registry[$adapter->name] = !isset($registry[$adapter->name]) ? $adapter : throw new LogicException(\sprintf(
                'Executor adapter with same connection name "%s" is already registered in registry.',
                $adapter->name,
            ));
        }

        $this->registry = $registry;
        $this->default  = $default ?? \array_values($this->registry)[0]->name;

        if (0 === \count($this->registry)) {
            throw new LogicException('There must be at least one adapter registered in registry.');
        }
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
        $connection = $connection ?? $this->default;

        return $this->registry[$connection] ?? throw new RuntimeException(\sprintf(
            'Executor adapter for connection name "%s" does not exists.',
            $connection
        ));
    }

    /**
     * Create default configuration objects for the adapter.
     *
     * If middlewares depend on adapter configurations, and configurations
     * adapter should provide default configuration which will be used during
     * execution process.
     *
     * @param class-string<TransactionInterface|ExecutionInterface> $class Type of requested default configuration.
     *
     * @return ($class is class-string<TransactionInterface> ? TransactionInterface : ExecutionInterface)
     */
    public function defaults(string $class): object
    {
        return $this->get()->defaults($class);
    }
}
