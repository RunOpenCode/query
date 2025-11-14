<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\TransactionExecutor;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;

/**
 * Default implementation of {@see ExecutorInterface}.
 *
 * @internal
 */
final class Executor implements ExecutorInterface
{
    /**
     * Denotes if executor is in current execution scope.
     */
    private bool $current = true;

    public function __construct(
        private readonly MiddlewareRegistry $middlewares,
        private readonly AdapterRegistry    $adapters,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        return $this->middlewares->query($query, new Context(
            configurations: $configuration
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, object ...$configuration): int
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        return $this->middlewares->statement($query, new Context(
            configurations: $configuration
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        $executor = new TransactionExecutor(
            $this->middlewares,
            $this->adapters,
            $transactional,
            \array_values($transaction),
        );

        $this->current = false;

        try {
            return $executor->__invoke();
        } finally {
            $this->current = true;
        }
    }
}
