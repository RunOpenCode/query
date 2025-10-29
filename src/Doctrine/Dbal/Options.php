<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use Doctrine\DBAL\TransactionIsolationLevel;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionalScope;

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
     * @param ?TransactionalScope        $scope      Optional transactional scope override, if query/statement is executed inside transactional scope.
     * @param non-empty-string[]|null    $tags       Optional executor tags.
     */
    public function __construct(
        public ?string                    $connection = null,
        public ?TransactionIsolationLevel $isolation = null,
        public ?TransactionalScope        $scope = null,
        public ?array                     $tags = null,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $tag): bool
    {
        return null !== $this->tags && \in_array($tag, $this->tags, true);
    }

    /**
     * Creates a new DbalOptions instance with specified connection.
     *
     * @param non-empty-string               $connection Optional connection name.
     * @param TransactionIsolationLevel|null $isolation  Optional transaction isolation level.
     * @param non-empty-string[]|null        $tags       Optional executor tags.
     */
    public static function connection(
        string                     $connection,
        ?TransactionIsolationLevel $isolation = null,
        ?TransactionalScope        $scope = null,
        ?array                     $tags = null,
    ): self {
        return new self($connection, $isolation, $scope, $tags);
    }

    /**
     * Creates DbalOptions with READ UNCOMMITTED isolation level.
     *
     * @param ?non-empty-string       $connection Optional connection name.
     * @param non-empty-string[]|null $tags       Optional executor tags.
     */
    public static function readUncommitted(
        ?string             $connection = null,
        ?TransactionalScope $scope = null,
        ?array              $tags = null
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_UNCOMMITTED,
            $scope,
            $tags,
        );
    }

    /**
     * Creates DbalOptions with READ COMMITTED isolation level.
     *
     * @param ?non-empty-string       $connection Optional connection name.
     * @param non-empty-string[]|null $tags       Optional executor tags.
     */
    public static function readCommitted(
        ?string             $connection = null,
        ?TransactionalScope $scope = null,
        ?array              $tags = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::READ_COMMITTED,
            $scope,
            $tags,
        );
    }

    /**
     * Creates DbalOptions with REPEATABLE READ isolation level.
     *
     * @param ?non-empty-string       $connection Optional connection name.
     * @param non-empty-string[]|null $tags       Optional executor tags.
     */
    public static function repeatableRead(
        ?string             $connection = null,
        ?TransactionalScope $scope = null,
        ?array              $tags = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::REPEATABLE_READ,
            $scope,
            $tags,
        );
    }

    /**
     * Creates DbalOptions with SERIALIZABLE isolation level.
     *
     * @param ?non-empty-string       $connection Optional connection name.
     * @param non-empty-string[]|null $tags       Optional executor tags.
     */
    public static function serializable(
        ?string             $connection = null,
        ?TransactionalScope $scope = null,
        ?array              $tags = null,
    ): self {
        return new self(
            $connection,
            TransactionIsolationLevel::SERIALIZABLE,
            $scope,
            $tags,
        );
    }
}
