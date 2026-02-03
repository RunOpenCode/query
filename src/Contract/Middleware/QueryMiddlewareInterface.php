<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Middleware;

use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;

/**
 * Middleware for query execution.
 *
 * @template TKey of array-key = array-key
 * @template TValue of mixed[]|object = mixed[]|object
 *
 * @phpstan-type Next = callable(non-empty-string, QueryContextInterface): ResultInterface<array-key, mixed[]|object>
 */
interface QueryMiddlewareInterface
{
    /**
     * Invoke middleware for query.
     *
     * @param non-empty-string      $query   Query to execute.
     * @param QueryContextInterface $context Query execution context.
     * @param Next                  $next    Next middleware to call.
     *
     * @return ResultInterface<TKey, TValue> Result of query execution.
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface;
}
