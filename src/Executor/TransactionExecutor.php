<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Exception\AdapterAwareExceptionInterface;
use RunOpenCode\Component\Query\Exception\TransactionScopeRollbackException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\ContextFactory;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;

/**
 * Transaction executor executes queries and statements within transaction scope.
 *
 * @internal
 *
 * @template T
 */
final class TransactionExecutor implements ExecutorInterface
{
    /**
     * A callable which needs to be invoked within transaction.
     *
     * @var \Closure(ExecutorInterface): T
     */
    private readonly \Closure $callable;

    /**
     * Denotes if executor is in current execution scope.
     */
    private bool $current = false;

    /**
     * Create new transaction executor.
     *
     * @param MiddlewareChain                $middlewares Registry of middlewares.
     * @param AdapterRegistry                $adapters    Registry of execution adapters.
     * @param callable(ExecutorInterface): T $callable    Callable to invoke within transactional scope.
     */
    public function __construct(
        private readonly MiddlewareChain             $middlewares,
        private readonly AdapterRegistry             $adapters,
        private readonly TransactionContextInterface $context,
        callable                                     $callable,
    ) {
        $this->callable = $callable(...);
    }

    /**
     * Execute transaction.
     *
     * @return T Return value of callable executed within transaction.
     *
     * @internal
     */
    public function __invoke(): mixed
    {
        $inTransaction = [];

        try {
            foreach ($this->context as $connection => $configuration) {
                $adapter = $this->adapters->get($connection);

                $adapter->begin($configuration);

                $inTransaction[] = $adapter;
            }

            $this->current = true;

            $result = ($this->callable)($this);

            foreach ($inTransaction as $adapter) {
                $adapter->commit();
            }

            return $result;
        } catch (\Exception $exception) {
            $trace = [];

            foreach ($inTransaction as $adapter) {
                try {
                    $failed = $exception instanceof AdapterAwareExceptionInterface && $exception->adapter === $adapter;
                    $adapter->rollback($failed ? $exception : null);
                } catch (\Exception $exception) {
                    $trace[$adapter->name] = $exception;
                }
            }

            throw $trace === [] ? $exception : new TransactionScopeRollbackException(\sprintf(
                'Unable to rollback transaction for connections: "%s".',
                \implode('", "', \array_keys($trace))
            ), $exception, ...\array_values($trace));
        } finally {
            $this->current = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        $context = ContextFactory::instance($this->adapters)->query($query, $this->context, ...$configuration);

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

        $context = ContextFactory::instance($this->adapters)->statement($statement, $this->context, ...$configuration);

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

        $context  = ContextFactory::instance($this->adapters)->transaction($this->context, ...$configuration);
        $executor = new self(
            $this->middlewares,
            $this->adapters,
            $context,
            $function,
        );

        try {
            return $executor->__invoke();
        } finally {
            $this->current = true;
        }
    }
}
