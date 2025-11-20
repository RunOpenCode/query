<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;

/**
 * Options for Doctrine Dbal executor.
 */
final readonly class Options implements OptionsInterface
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
     * Creates a new DbalOptions instance with specified connection.
     *
     * @param non-empty-string               $connection Optional connection name.
     * @param TransactionIsolationLevel|null $isolation  Optional transaction isolation level.
     */
    public static function connection(
        string                     $connection,
        ?TransactionIsolationLevel $isolation = null,
        ?ExecutionScope            $scope = null,
    ): self {
        return new self($connection, $isolation, $scope);
    }

    /**
     * Creates DbalOptions with READ UNCOMMITTED isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
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
     * Creates DbalOptions with READ COMMITTED isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
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
     * Creates DbalOptions with REPEATABLE READ isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
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
     * Creates DbalOptions with SERIALIZABLE isolation level.
     *
     * @param ?non-empty-string $connection Optional connection name.
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
