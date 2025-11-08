<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\TransactionExecutor;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;

/**
 * Default implementation of {@see ExecutorInterface}.
 *
 * @internal
 */
final readonly class Executor implements ExecutorInterface
{
    public function __construct(
        private MiddlewareRegistry $middlewares,
        private AdapterRegistry    $adapters,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        return $this->middlewares->query($query, new Context(
            configuration: $configuration
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, object ...$configuration): int
    {
        return $this->middlewares->statement($query, new Context(
            configuration: $configuration
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed
    {
        /** @var list<TransactionInterface> $transaction */
        return new TransactionExecutor(
            $this->middlewares,
            $this->adapters,
            $transactional,
            $transaction
        )();
    }
}
