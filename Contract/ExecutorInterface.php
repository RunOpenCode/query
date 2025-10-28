<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract;

use Doctrine\DBAL\Exception\SyntaxErrorException;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\UnsupportedException;

/**
 * Query/statement/transaction executor.
 *
 * Executor is main entry point for executing statements/transactions with or without
 * transactional scope.
 */
interface ExecutorInterface
{
    /**
     * Execute query through middleware chain.
     *
     * @param non-empty-string $query            Query to execute.
     * @param object           ...$configuration Configuration objects for middlewares.
     *
     * @return ResultInterface Result of execution.
     *
     * @throws ConnectionException If there is a connection problem while executing query.
     * @throws SyntaxErrorException If provided query has syntax errors, or if syntax error is detected during query parsing phase using configured language.
     * @throws DeadlockException If execution of query was impossible due to deadlock.
     * @throws DriverException If execution of query fails due to driver related issues.
     * @throws RuntimeException If unknown error occurred.
     * @throws InvalidArgumentException If middleware configuration is invalid.
     * @throws LogicException If there is a problem with execution logic and requires either reconfiguration or refactoring.
     */
    public function query(string $query, object ...$configuration): ResultInterface;

    /**
     * Execute statement through middleware chain.
     *
     * @param non-empty-string $query            Query to execute.
     * @param object           ...$configuration Configuration objects for middlewares.
     *
     * @return int Number of affected records.
     *
     * @throws ConnectionException If there is a connection problem while executing statement.
     * @throws SyntaxErrorException If provided statement has syntax errors, or if syntax error is detected during statement parsing phase using configured language.
     * @throws DeadlockException If execution of statement was impossible due to deadlock.
     * @throws DriverException If execution of statement fails due to driver related issues.
     * @throws RuntimeException If unknown error occurred.
     * @throws InvalidArgumentException If middleware configuration is invalid.
     * @throws LogicException If there is a problem with execution logic and requires either reconfiguration or refactoring.
     */
    public function statement(string $query, object ...$configuration): int;

    /**
     * Execute queries and statements inside transactional scope.
     *
     * 
     *
     * @template T
     *
     * @param callable(ExecutorInterface): T $transactional  Function to be executed inside transactional scope.
     * @param TransactionInterface           ...$transaction Transaction configuration, denoting which connections should create
     *                                                       transactional scope. If none provided, default will be used.
     *
     * @throws ConnectionException If there is a connection problem while executing transaction.
     * @throws SyntaxErrorException If statement/query within transaction has syntax errors, or if syntax error is detected during parsing phase using configured language.
     * @throws DeadlockException If execution of statement/query was impossible due to deadlock.
     * @throws DriverException If execution of statement/query fails due to driver related issues.
     * @throws RuntimeException If unknown error occurred.
     * @throws InvalidArgumentException If middleware configuration is invalid.
     * @throws LogicException If there is a problem with execution logic and requires either reconfiguration or refactoring.
     * @throws UnsupportedException If used adapter do not supports transactions.
     *                                                       
     * @return T
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed;
}
