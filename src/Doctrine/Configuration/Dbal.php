<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Configuration;

use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;

/**
 * Configuration for Doctrine Dbal executor.
 */
final readonly class Dbal implements ExecutionInterface
{
    /**
     * Creates a new DbalOptions instance.
     *
     * @param ?non-empty-string          $connection Optional connection name.
     * @param ?TransactionIsolationLevel $isolation  Optional transaction isolation level.
     * @param ?ExecutionScope            $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public function __construct(
        public ?string                    $connection = null,
        public ?TransactionIsolationLevel $isolation = null,
        public ?ExecutionScope            $scope = null,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function withConnection(string $connection): self
    {
        return new self(
            $connection,
            $this->isolation,
            $this->scope,
        );
    }

    /**
     * Set transaction isolation level.
     *
     * @param TransactionIsolationLevel $level Transaction isolation level.
     *
     * @return self New instance of executor adapter configuration with isolation level set.
     */
    public function withIsolation(TransactionIsolationLevel $level): self
    {
        return new self(
            $this->connection,
            $level,
            $this->scope,
        );
    }

    /**
     * Set transaction execution scope.
     *
     * @param ExecutionScope $scope Transaction execution scope.
     *
     * @return self New instance of executor adapter configuration with transaction execution scope set.
     */
    public function withExecutionScope(ExecutionScope $scope): self
    {
        return new self(
            $this->connection,
            $this->isolation,
            $scope,
        );
    }

    /**
     * Set transaction isolation level to READ UNCOMMITED.
     *
     * @return self New instance of executor adapter configuration with isolation level READ UNCOMMITED set.
     */
    public function withReadUncommitedIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
            $this->scope,
        );
    }

    /**
     * Set transaction isolation level to REPEATABLE READ.
     *
     * @return self New instance of executor adapter configuration with isolation level REPEATABLE READ set.
     */
    public function withRepeatableReadIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::REPEATABLE_READ,
            $this->scope,
        );
    }

    /**
     * Set transaction isolation level to READ COMMITTED.
     *
     * @return self New instance of executor adapter configuration with isolation level READ COMMITTED set.
     */
    public function withReadCommitedIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::READ_COMMITTED,
            $this->scope,
        );
    }

    /**
     * Set transaction isolation level to SERIALIZABLE.
     *
     * @return self New instance of executor adapter configuration with isolation level SERIALIZABLE set.
     */
    public function withSerializableIsolation(): self
    {
        return new self(
            $this->connection,
            TransactionIsolationLevel::SERIALIZABLE,
            $this->scope,
        );
    }

    /**
     * Creates a new configuration instance with specified connection.
     *
     * @param non-empty-string               $connection Connection name.
     * @param TransactionIsolationLevel|null $isolation  Optional transaction isolation level.
     * @param ?ExecutionScope                $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public static function connection(
        string                     $connection,
        ?TransactionIsolationLevel $isolation = null,
        ?ExecutionScope            $scope = null,
    ): self {
        return new self($connection, $isolation, $scope);
    }

    /**
     * Creates a new configuration instance with READ UNCOMMITTED isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
     * @param ?ExecutionScope   $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public static function readUncommitted(
        ?string         $connection = null,
        ?ExecutionScope $scope = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
            $scope,
        );
    }

    /**
     * Creates a new configuration instance with READ COMMITTED isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
     * @param ?ExecutionScope   $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public static function readCommitted(
        ?string         $connection = null,
        ?ExecutionScope $scope = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_COMMITTED,
            $scope,
        );
    }

    /**
     * Creates a new configuration instance with REPEATABLE READ isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
     * @param ?ExecutionScope   $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public static function repeatableRead(
        ?string         $connection = null,
        ?ExecutionScope $scope = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::REPEATABLE_READ,
            $scope,
        );
    }

    /**
     * Creates a new configuration instance with SERIALIZABLE isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
     * @param ?ExecutionScope   $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     */
    public static function serializable(
        ?string         $connection = null,
        ?ExecutionScope $scope = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::SERIALIZABLE,
            $scope,
        );
    }
}
