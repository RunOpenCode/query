<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Exception\TransactionScopeRollbackException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;

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
     * @var list<TransactionInterface>
     */
    private readonly array $configurations;

    /**
     * Current transaction scope.
     */
    private TransactionScope|null $scope;

    /**
     * Flag denoting if transaction execution have been closed.
     */
    private bool $closed;

    /**
     * Denotes if executor is in current execution scope.
     */
    private bool $current;

    /**
     * Create new transaction executor.
     *
     * @param MiddlewareRegistry             $middlewares    Registry of middlewares.
     * @param AdapterRegistry                $adapters       Registry of execution adapters.
     * @param callable(ExecutorInterface): T $callable       Callable to invoke within transactional scope.
     * @param list<TransactionInterface>     $configurations List of configurations of adapters for which transactional scope should be created.
     */
    public function __construct(
        private readonly MiddlewareRegistry $middlewares,
        private readonly AdapterRegistry    $adapters,
        callable                            $callable,
        array                               $configurations
    ) {
        $this->callable       = $callable(...);
        $this->configurations = $configurations;
        $this->scope          = null;
        $this->closed         = false;
        $this->current        = false;

        $this->assertValidScope();
    }

    /**
     * Execute transaction.
     *
     * @param ?TransactionScope $parent Parent transaction scope, if any.
     *
     * @return T Return value of callable executed within transaction.
     *
     * @internal
     */
    public function __invoke(?TransactionScope $parent = null): mixed
    {
        if ($this->closed) {
            throw new LogicException('Execution of this transaction is closed.');
        }

        $configurations = empty($this->configurations) ? [null] : $this->configurations;
        $adapters       = [];

        try {
            foreach ($configurations as $configuration) {
                $adapter = $this->adapters->get($configuration?->connection);

                $adapter->begin($configuration);

                $adapters[] = $adapter;
            }

            $this->scope   = new TransactionScope($adapters, $parent);
            $this->current = true;

            $result = ($this->callable)($this);

            foreach ($adapters as $adapter) {
                $adapter->commit();
            }

            return $result;
        } catch (\Exception $exception) {
            $trace = [];

            foreach ($adapters as $adapter) {
                try {
                    $adapter->rollback();
                } catch (\Exception $exception) {
                    $trace[$adapter->name] = $exception;
                }
            }

            throw empty($trace) ? $exception : new TransactionScopeRollbackException(\sprintf(
                'Unable to rollback transaction for connections: "%s".',
                \implode('", "', \array_keys($trace))
            ), $exception, ...\array_values($trace));
        } finally {
            $this->closed  = true;
            $this->current = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        if ($this->closed) {
            throw new LogicException('Execution of this transaction is closed.');
        }

        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        \assert(null !== $this->scope);

        $context = new Context(
            configurations: $configuration,
            transaction: $this->scope,
        );

        return $this->middlewares->query($query, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, object ...$configuration): int
    {
        if ($this->closed) {
            throw new LogicException('Execution of this transaction is closed.');
        }

        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        \assert(null !== $this->scope);

        $context = new Context(
            configurations: $configuration,
            transaction: $this->scope,
        );

        return $this->middlewares->statement($query, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed
    {
        if (!$this->current) {
            throw new LogicException('You are invoking method of executor which is not in current transactional scope.');
        }

        \assert(null !== $this->scope);

        $executor = new self(
            $this->middlewares,
            $this->adapters,
            $transactional,
            \array_values($transaction),
        );

        $this->current = false;

        try {
            return $executor->__invoke($this->scope);
        } finally {
            $this->current = true;
        }
    }

    /**
     * Assert that transaction scope is valid.
     */
    private function assertValidScope(): void
    {
        $connections = \array_map(
            static fn(TransactionInterface $transaction) => $transaction->connection,
            $this->configurations,
        );

        if (\count($this->configurations) !== \count($connections)) {
            throw new LogicException(\sprintf(
                'Transaction scope can not be created using same connection multiple times ("%s").',
                \implode('", "', \array_keys(
                    \array_filter(
                        \array_count_values($connections),
                        static fn(int $count, string $name): bool => $count > 1,
                        \ARRAY_FILTER_USE_BOTH
                    )
                ))
            ));
        }
    }
}
