<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;

/**
 * Current transaction scope.
 *
 * This value object consist of all transaction configurations used for creating
 * new transaction execution scope, with reference to previous transaction scope,
 * if exists.
 *
 * @internal
 */
final readonly class TransactionScope
{
    /**
     * Hashmap of all connection names used for creating this transaction scope.
     *
     * @var array<string, string>
     */
    private array $connections;

    /**
     * Create new transaction scope.
     *
     * @param non-empty-list<TransactionInterface> $configurations Transaction configurations which were used to commence this transaction scope.
     * @param TransactionScope|null                $parent         Parent transaction scope, if exists.
     */
    public function __construct(
        private array             $configurations,
        private ?TransactionScope $parent = null,
    ) {
        $connections = \array_map(
            static fn(TransactionInterface $configuration): string => $configuration->connection,
            $this->configurations
        );

        $this->connections = \array_combine($connections, $connections);
    }

    /**
     * Check if connection can execute query/statement within current transactional scope using
     * defined transactional scope configuration.
     *
     * @param string         $connection Connection to check.
     * @param ExecutionScope $scope      Transactional scope configuration.
     *
     * @return bool
     */
    public function accepts(string $connection, ExecutionScope $scope): bool
    {
        if (ExecutionScope::None === $scope) {
            return true;
        }

        if (ExecutionScope::Parent === $scope) {
            return isset($this->connections[$connection]) || $this->parent?->accepts($connection, $scope);
        }

        return isset($this->connections[$connection]);
    }
}
