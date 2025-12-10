<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Configuration;

use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;

/**
 * Doctrine transaction configuration.
 *
 * Used for Dbal and Orm adapters.
 */
final readonly class Transaction implements TransactionInterface
{
    /**
     * Creates new Doctrine transaction instance.
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
     * {@inheritdoc}
     */
    public function withConnection(string $connection): TransactionInterface
    {
        return new self($connection, $this->isolation);
    }

    /**
     * Set transaction isolation level.
     *
     * @param TransactionIsolationLevel $level Transaction isolation level.
     *
     * @return self New instance of transaction configuration with isolation level set.
     */
    public function withIsolation(TransactionIsolationLevel $level): self
    {
        return new self(
            $this->connection,
            $level,
        );
    }

    /**
     * Set transaction isolation level to READ UNCOMMITED.
     *
     * @return self New instance of transaction configuration with isolation level READ UNCOMMITED set.
     */
    public function withReadUncommitedIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
        );
    }

    /**
     * Set transaction isolation level to REPEATABLE READ.
     *
     * @return self New instance of transaction configuration with isolation level REPEATABLE READ set.
     */
    public function withRepeatableReadIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::REPEATABLE_READ,
        );
    }

    /**
     * Set transaction isolation level to READ COMMITTED.
     *
     * @return self New instance of transaction configuration with isolation level READ COMMITTED set.
     */
    public function withReadCommitedIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::READ_COMMITTED,
        );
    }

    /**
     * Set transaction isolation level to SERIALIZABLE.
     *
     * @return self New instance of transaction configuration with isolation level SERIALIZABLE set.
     */
    public function withSerializableIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::SERIALIZABLE,
        );
    }

    /**
     * Creates new Doctrine transaction instance for connection.
     *
     * @param non-empty-string $connection Connection name.
     */
    public static function connection(string $connection): self
    {
        return new self($connection);
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
            TransactionIsolationLevel::READ_COMMITTED,
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
