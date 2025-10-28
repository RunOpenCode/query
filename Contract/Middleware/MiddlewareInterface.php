<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Middleware;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;

/**
 * Execution middleware.
 *
 * @phpstan-type NextMiddlewareQueryCallable = callable(string, ContextInterface): ResultInterface
 * @phpstan-type NextMiddlewareStatementCallable = callable(string, ContextInterface): int
 */
interface MiddlewareInterface
{
    /**
     * Invoke middleware for query.
     *
     * @param non-empty-string            $query   Query to execute.
     * @param ContextInterface            $context Middleware execution context.
     * @param NextMiddlewareQueryCallable $next    Next middleware to call, if applicable.
     *
     * @return ResultInterface Result of query execution.
     */
    public function query(string $query, ContextInterface $context, callable $next): ResultInterface;

    /**
     * Invoke middleware for statement.
     *
     * @param non-empty-string                $query   Query to execute.
     * @param ContextInterface                $context Middleware execution context.
     * @param NextMiddlewareStatementCallable $next    Next middleware to call, if applicable.
     *
     * @return int Number of affected records.
     */
    public function statement(string $query, ContextInterface $context, callable $next): int;
}
