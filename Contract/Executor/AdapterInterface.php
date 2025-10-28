<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;

/**
 * @phpstan-type NamedParameters = ParametersInterface<non-empty-string>
 * @phpstan-type PositionalParameters = ParametersInterface<non-negative-int>
 * @phpstan-type Parameters = NamedParameters|PositionalParameters
 *
 * @template TTransaction of TransactionInterface
 * @template TOptions of OptionsInterface
 * @template TResult of ResultInterface
 */
interface AdapterInterface
{
    /**
     * Adapter name.
     *
     * Adapter name represents unique identifier of the adapter. In practice,
     * it is the same name as the connection name used to establish connection
     * to data source.
     *
     * Each registered adapter within executor must have unique name.
     *
     * @return non-empty-string
     */
    public string $name {
        get;
    }

    /**
     * Begin transaction.
     *
     * Start transaction using adapter's connection, according to the given configuration.
     *
     * If configuration is not provided, adapter should use default configuration defined
     * by adapters itself.
     *
     * @param TTransaction|null $transaction Optional transaction configuration to use, or use default configuration.
     *
     * @return TTransaction Used transaction configuration.
     */
    public function begin(?TransactionInterface $transaction): TransactionInterface;

    /**
     * Commit current transaction.
     *
     * @param TTransaction $transaction Used transaction configuration for transaction which is being commited.
     */
    public function commit(TransactionInterface $transaction): void;

    /**
     * Rollback current transaction.
     *
     * @param TTransaction $transaction Used transaction configuration for transaction which is being rolled back.
     */
    public function rollback(TransactionInterface $transaction): void;

    /**
     * Execute selection query.
     *
     * This query is expected to return rows of data.
     *
     * @param non-empty-string $query      Query to execute.
     * @param Parameters|null  $parameters Optional parameters for query.
     * @param TOptions|null    $options    Optional executor specific options.
     *
     * @return TResult Result of execution.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws DriverException If execution fails.
     * @throws SyntaxException If provided query has syntax errors.
     * @throws RuntimeException If unknown error occurred.
     */
    public function query(string $query, ?ParametersInterface $parameters = null, ?OptionsInterface $options = null): ResultInterface;

    /**
     * Execute statement query.
     *
     * This query is expected to perform data manipulation and return number of affected records.
     *
     * @param non-empty-string $query      Query to execute.
     * @param Parameters|null  $parameters Optional parameters for query.
     * @param TOptions|null    $options    Optional executor specific options.
     *
     * @return int Number of affected records.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws DriverException If execution fails.
     * @throws SyntaxException If provided query has syntax errors.
     * @throws RuntimeException If unknown error occurred.
     */
    public function statement(string $query, ?ParametersInterface $parameters = null, ?OptionsInterface $options = null): int;
}
