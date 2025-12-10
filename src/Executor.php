<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query;

use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\TransactionExecutor;
use RunOpenCode\Component\Query\Middleware\ContextFactory;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;
use RunOpenCode\Component\Query\Middleware\TransactionContext;

/**
 * Default implementation of {@see ExecutorInterface}.
 */
final class Executor implements ExecutorInterface
{
    /**
     * Denotes if executor is in current execution scope.
     */
    private bool $current = true;

    public function __construct(
        private readonly MiddlewareChain $middlewares,
        private readonly AdapterRegistry $adapters,
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

        $context = ContextFactory::instance($this->adapters)->query($query, null, ...$configuration);

        return $this->middlewares->query($query, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, object ...$configuration): AffectedInterface
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        $context = ContextFactory::instance($this->adapters)->statement($statement, null, ...$configuration);

        return $this->middlewares->statement($statement, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $function, object ...$configuration): mixed
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        $this->current = false;

        $context = ContextFactory::instance($this->adapters)->transaction(null, ...$configuration);

        try {
            return $this->middlewares->transactional(new TransactionExecutor(
                $this->middlewares,
                $this->adapters,
                $context,
                $function
            ), $context);
        } finally {
            $this->current = true;
        }
    }
}
