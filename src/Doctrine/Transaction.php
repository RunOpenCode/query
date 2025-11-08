<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine;

use Doctrine\DBAL\TransactionIsolationLevel;
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
    ) {
        // noop.
    }

    /**
     * Create transaction configuration with READ UNCOMMITED isolation level.
     *
     * @param non-empty-string $connection Connection for which transaction scope should be created.
     */
    public static function readUncommitted(string $connection): self
    {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
        );
    }

    /**
     * Create transaction configuration with READ COMMITED isolation level.
     *
     * @param non-empty-string $connection Connection for which transaction scope should be created.
     */
    public static function readCommitted(string $connection): self
    {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
        );
    }

    /**
     * Create transaction configuration with REPEATABLE READ isolation level.
     *
     * @param non-empty-string $connection Connection for which transaction scope should be created.
     */
    public static function repeatableRead(string $connection): self
    {
        return new self(
            $connection,
            TransactionIsolationLevel::REPEATABLE_READ,
        );
    }

    /**
     * Create transaction configuration with SERIALIZABLE isolation level.
     *
     * @param non-empty-string $connection Connection for which transaction scope should be created.
     */
    public static function serializable(string $connection): self
    {
        return new self(
            $connection,
            TransactionIsolationLevel::SERIALIZABLE,
        );
    }
}
