<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Middleware;

use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;

/**
 * @phpstan-type TransactionalFn = callable(): mixed
 * @phpstan-type Next = callable(TransactionalFn, TransactionContextInterface): mixed
 */
interface TransactionMiddlewareInterface
{
    /**
     * Invoke middleware for transaction.
     *
     * @param TransactionalFn             $function Function to be executed inside transactional scope.
     * @param TransactionContextInterface $context  Transaction context.
     * @param Next                        $next     Next middleware to call.
     */
    public function transactional(callable $function, TransactionContextInterface $context, callable $next): mixed;
}
