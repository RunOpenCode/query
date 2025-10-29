<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\TransactionalScope;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;

final readonly class TransactionStack
{
    /**
     * Size of transaction stack.
     *
     * @var int
     */
    public int $size;

    /**
     * A list of acceptable connections for TransactionalScope::Strict mode.
     *
     * @var non-empty-array<non-empty-string, bool>
     */
    private array $strict;

    /**
     * A list of acceptable connections for TransactionalScope::Parent mode.
     *
     * @var non-empty-array<non-empty-string, bool>
     */
    private array $parent;

    /**
     * @param non-empty-list<non-empty-list<TransactionInterface>> $transactions
     */
    private function __construct(private array $transactions)
    {
        $this->size = \count($this->transactions);
        $strict     = [];
        $parent     = [];

        foreach ($this->transactions as $index => $scope) {
            foreach ($scope as $transaction) {
                if (0 === $index) {
                    $strict[$transaction->connection] = true;
                }

                $parent[$transaction->connection] = true;
            }
        }

        // @phpstan-ignore-next-line
        $this->strict = $strict;
        $this->parent = $parent;
    }

    /**
     * Add transaction scope to transaction stack.
     *
     * @param non-empty-list<TransactionInterface> $configurations
     *
     * @return self
     */
    public function push(array $configurations): TransactionStack
    {
        return new self([
            $configurations,
            ...$this->transactions,
        ]);
    }

    /**
     * Check if connection can execute query/statement within current transactional scope using
     * defined transactional scope configuration.
     *
     * @param string             $connection Connection to check.
     * @param TransactionalScope $scope      Transactional scope configuration.
     *
     * @return bool
     */
    public function accepts(string $connection, TransactionalScope $scope): bool
    {
        if (TransactionalScope::None === $scope) {
            return true;
        }

        if (TransactionalScope::Strict === $scope) {
            return isset($this->strict[$connection]);
        }

        return isset($this->parent[$connection]);
    }

    /**
     * Initialize transaction stack.
     *
     * @param non-empty-list<TransactionInterface> $configurations
     *
     * @return self
     */
    public static function create(array $configurations): self
    {
        return new self([$configurations]);
    }
}
