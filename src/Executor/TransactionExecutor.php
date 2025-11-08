<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;
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

        $this->closed   = true;
        $configurations = empty($this->configurations) ? [null] : $this->configurations;

        /**
         * A list of adapters and applied configurations for which transaction successfully started.
         *
         * @var list<array{AdapterInterface, TransactionInterface}> $transactional
         */
        $transactional = [];

        try {
            foreach ($configurations as $configuration) {
                $adapter       = $this->adapters->get($configuration?->connection);
                $configuration = $adapter->begin($configuration);

                $transactional[] = [$adapter, $configuration];
            }

            $this->scope = new TransactionScope(\array_map(
                static fn(array $current): TransactionInterface => $current[1],
                $transactional,
            ), $parent);

            $result = ($this->callable)($this);

            foreach ($transactional as [$adapter, $configuration]) {
                $adapter->commit($configuration);
            }

            return $result;
        } catch (\Exception $exception) {
            $trace = [];

            foreach ($transactional as [$adapter, $configuration]) {
                try {
                    $adapter->rollback($configuration);
                } catch (\Exception $exception) {
                    $trace[$configuration->connection] = $exception;
                }
            }

            throw empty($trace) ? $exception : new TransactionScopeRollbackException(\sprintf(
                'Unable to rollback transaction for connections: "%s".',
                \implode('", "', \array_keys($trace))
            ), $exception, ...\array_values($trace));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        \assert(null !== $this->scope);

        $context = new Context(
            configuration: $configuration,
            transaction: $this->scope,
        );

        $options    = $context->peak(OptionsInterface::class);
        $scope      = $options->scope ?? ExecutionScope::Strict;
        $connection = $options->connection ?? $this->adapters->get()->name;

        if ($this->scope->accepts($connection, $scope)) {
            return $this->middlewares->query($query, $context);
        }

        throw new LogicException(\sprintf(
            'Execution of query "%s" using connection "%s" within transaction violates current transactional scope execution configuration "%s".',
            $query,
            $connection,
            $scope->name,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, object ...$configuration): int
    {
        \assert(null !== $this->scope);

        $context = new Context(
            configuration: $configuration,
            transaction: $this->scope,
        );

        $options    = $context->peak(OptionsInterface::class);
        $scope      = $options->scope ?? ExecutionScope::Strict;
        $connection = $options->connection ?? $this->adapters->get()->name;

        if ($this->scope->accepts($connection, $scope)) {
            return $this->middlewares->statement($query, $context);
        }

        throw new LogicException(\sprintf(
            'Execution of statement "%s" using connection "%s" within transaction violates current transactional scope execution configuration "%s".',
            $query,
            $connection,
            $scope->name,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed
    {
        \assert(null !== $this->scope);

        /** @var list<TransactionInterface> $transaction */
        return new self(
            $this->middlewares,
            $this->adapters,
            $transactional,
            $transaction,
        )->__invoke($this->scope);
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
