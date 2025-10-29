<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine;

use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Executor\TransactionalScope;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;

/**
 * Doctrine transaction configuration.
 *
 * Used for Dbal and Orm adapters.
 */
final readonly class Transaction implements TransactionInterface
{
    /**
     * Creates a new Doctrine transaction instance.
     *
     * Object of this class can configure Dbal and Orm transaction
     * as well, since both use the same configuration options.
     *
     * @param non-empty-string               $connection Optional connection name.
     * @param TransactionIsolationLevel|null $isolation  Optional transaction isolation level.
     */
    public function __construct(
        public string                     $connection,
        public ?TransactionIsolationLevel $isolation = null,
        public TransactionalScope         $query = TransactionalScope::Strict,
        public TransactionalScope         $statement = TransactionalScope::Strict,
    ) {
        // noop.
    }
}
