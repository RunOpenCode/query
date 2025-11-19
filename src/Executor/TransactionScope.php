<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;

/**
 * Current transaction scope.
 *
 * This value object consist of all adapters used for creating transaction scope,
 * configurations used for creating that transaction scope, with reference to previous
 * transaction scope, if exists.
 *
 * @internal
 */
final readonly class TransactionScope
{
    /**
     * @var array<non-empty-string, AdapterInterface>
     */
    private array $adapters;

    /**
     * Create new transaction scope.
     *
     * @param non-empty-list<AdapterInterface> $adapters List of adapters with opened transactional scope.
     * @param TransactionScope|null            $parent   Parent transaction scope, if exists.
     */
    public function __construct(
        array                     $adapters,
        private ?TransactionScope $parent = null,
    ) {
        $this->adapters = \array_combine(\array_map(
            static fn(AdapterInterface $adapter): string => $adapter->name,
            $adapters,
        ), $adapters);
    }

    /**
     * Check if adapter can execute query/statement within current transactional scope using
     * defined transactional scope configuration.
     *
     * @param AdapterInterface|non-empty-string $adapter Adapter to check (by name or reference).
     * @param ExecutionScope                    $scope   Transactional scope configuration.
     *
     * @return bool
     */
    public function accepts(AdapterInterface|string $adapter, ExecutionScope $scope): bool
    {
        if (ExecutionScope::None === $scope) {
            return true;
        }

        $name = $adapter instanceof AdapterInterface ? $adapter->name : $adapter;

        if (ExecutionScope::Strict === $scope) {
            return isset($this->adapters[$name]);
        }

        return isset($this->adapters[$name]) || $this->parent?->accepts($adapter, $scope);
    }
}
