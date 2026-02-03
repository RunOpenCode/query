<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\DriverSyntaxException;
use RunOpenCode\Component\Query\Exception\LockWaitTimeoutException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\TransactionException;

/**
 * Query executor uses adapters to execute queries and statements.
 *
 * Each supported database storage (or abstraction) should implement:
 *
 * - Its own adapter, dealing with low level query calls,
 * - Value object/objects that represents parameters for query execution (if supported by adapter). Based on
 *   internal workings of adapter, as well as API design decision, implementation may provide support for
 *   positional and named parameters separately, or all together.
 * - Value object which configures query and/or statement execution.
 * - Value object which configures query and/or statement execution within transactional scope.
 *
 * @template TTransaction of TransactionInterface = TransactionInterface
 * @template TConfiguration of ExecutionInterface = ExecutionInterface
 * @template TResult of ResultInterface = ResultInterface<array-key, mixed[]|object>
 */
interface AdapterInterface
{
    /**
     * Adapter's connection name.
     *
     * Adapter's connection name represents unique identifier of the
     * adapter. Each registered adapter within executor must have unique
     * connection name.
     *
     * @var non-empty-string
     */
    public string $name {
        get;
    }

    /**
     * Create default configuration objects for the adapter.
     *
     * If adapter options or transaction options are not passed to the executor,
     * defaults ought to be used. Executor must be able to get defaults from the
     * adapter itself.
     *
     * @param class-string<TransactionInterface|ExecutionInterface> $class Type of requested default configuration.
     *
     * @return ($class is class-string<TransactionInterface> ? TTransaction : TConfiguration)
     */
    public function defaults(string $class): object;

    /**
     * Begin transaction.
     *
     * Start transaction using adapter's connection, according to the given configuration.
     *
     * @param TTransaction $transaction Transaction configuration to use.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws TransactionException If transaction error occurred.
     * @throws DriverException If execution fails.
     * @throws RuntimeException If unknown error occurred.
     */
    public function begin(TransactionInterface $transaction): void;

    /**
     * Commit current transaction.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws TransactionException If transaction error occurred.
     * @throws DriverException If execution fails.
     * @throws RuntimeException If unknown error occurred.
     */
    public function commit(): void;

    /**
     * Rollback current transaction.
     *
     * @param \Throwable|null $exception Exception which was thrown during the execution. If exception was caused by some other
     *                                   adapter in distributed transaction, or for any other reason unrelated to execution,
     *                                   NULL is provided.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws TransactionException If transaction error occurred.
     * @throws DriverException If execution fails.
     * @throws RuntimeException If unknown error occurred.
     */
    public function rollback(?\Throwable $exception): void;

    /**
     * Execute query.
     *
     * This method is expected to return collection of records.
     *
     * @param non-empty-string         $query         Query to execute.
     * @param TConfiguration           $configuration Executor configuration.
     * @param ParametersInterface|null $parameters    Optional parameters for query.
     *
     * @return TResult Result of execution.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws DriverException If execution fails.
     * @throws DeadlockException If deadlock error occurred.
     * @throws LockWaitTimeoutException If lock wait timeout error occurred.
     * @throws DriverSyntaxException If provided query has syntax errors.
     * @throws RuntimeException If unknown error occurred.
     */
    public function query(string $query, ExecutionInterface $configuration, ?ParametersInterface $parameters = null): ResultInterface;

    /**
     * Execute statement.
     *
     * This method is expected to perform data manipulation and return report about affected database objects.
     *
     * @param non-empty-string         $query         Query to execute.
     * @param TConfiguration           $configuration Executor configuration.
     * @param ParametersInterface|null $parameters    Optional parameters for query.
     *
     * @return AffectedInterface Report about affected database objects.
     *
     * @throws ConnectionException If connection to data source could not be established.
     * @throws DriverException If execution fails.
     * @throws DeadlockException If deadlock error occurred.
     * @throws LockWaitTimeoutException If lock wait timeout error occurred.
     * @throws DriverSyntaxException If provided query has syntax errors.
     * @throws RuntimeException If unknown error occurred.
     */
    public function statement(string $query, ExecutionInterface $configuration, ?ParametersInterface $parameters = null): AffectedInterface;
}
