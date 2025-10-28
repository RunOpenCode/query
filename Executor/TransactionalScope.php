<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Executor\TransactionInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Exception\DistributedTransactionRollbackException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;

/**
 * @internal
 *
 * @template T
 *
 * @phpstan-import-type GenericAdapter from AdapterRegistry
 */
final class TransactionalScope implements ExecutorInterface
{
    /**
     * @var \Closure(ExecutorInterface): T
     */
    private readonly \Closure $callable;

    /**
     * @var list<TransactionInterface>
     */
    private readonly array $configurations;

    private ?Context $context = null;

    /**
     * @param MiddlewareRegistry             $middlewares
     * @param AdapterRegistry                $adapters
     * @param callable(ExecutorInterface): T $callable
     * @param list<TransactionInterface>     $configurations
     */
    public function __construct(
        private readonly MiddlewareRegistry $middlewares,
        private readonly AdapterRegistry    $adapters,
        callable                            $callable,
        array                               $configurations
    ) {
        $this->callable       = $callable(...);
        $this->configurations = $configurations;
    }

    /**
     * @param ?Context $parent Parent transactional context, if any.
     *
     * @return T
     *
     * @internal
     */
    public function __invoke(?Context $parent = null): mixed
    {
        if (null !== $this->context) {
            throw new LogicException('Transactional scope is already executed.');
        }

        $scope          = \iterator_to_array($this->prepare());
        $configurations = [];

        try {
            foreach ($scope as $key => [$adapter, $configuration]) {
                $configuration    = $adapter->begin($configuration);
                $scope[$key][1]   = $configuration;
                $configurations[] = $configuration;
            }

            // @phpstan-ignore-next-line
            $stack         = null === $parent ? TransactionStack::create($configurations) : $parent->transaction->push($configurations);
            $this->context = new Context(transaction: $stack);

            $result = ($this->callable)($this);

            foreach ($scope as [$adapter, $configuration]) {
                $adapter->commit($configuration);
            }

            return $result;
        } catch (\Exception $exception) {
            $rollbackExceptions = [];

            foreach ($scope as [$adapter, $configuration]) {
                if (null === $configuration) {
                    // This transaction didn't even started, we can not rollback.
                    continue;
                }

                try {
                    $adapter->rollback($configuration);
                } catch (\Exception $exception) {
                    // If we can not rollback one transaction, we have to continue
                    // and try to rollback others.
                    $rollbackExceptions[] = $exception;
                }
            }

            throw empty($rollbackExceptions) ? $exception : new DistributedTransactionRollbackException('Unable to rollback some of the transactions.', $exception, $rollbackExceptions);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, object ...$configuration): ResultInterface
    {
        // TODO: Transaction check

        \assert(null !== $this->context);

        return $this->middlewares->query($query, new Context(
            configuration: $configuration,
            transaction: $this->context->transaction,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, object ...$configuration): int
    {
        // TODO: Transaction check

        \assert(null !== $this->context);

        return $this->middlewares->statement($query, new Context(
            configuration: $configuration,
            transaction: $this->context->transaction,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $transactional, TransactionInterface ...$transaction): mixed
    {
        /** @var list<TransactionInterface> $transaction */
        return new self(
            $this->middlewares,
            $this->adapters,
            $transactional,
            $transaction,
        )->__invoke($this->context);
    }

    /**
     * Prepare adapters and their configurations for transaction execution.
     *
     * @return iterable<int, array{GenericAdapter, ?TransactionInterface}> Adapter/transaction configuration pair.
     */
    private function prepare(): iterable
    {
        $configuration = empty($this->configurations) ? [null] : $this->configurations;
        /** @var GenericAdapter[] $adapters */
        $adapters    = \array_map(
            fn(?TransactionInterface $transaction): AdapterInterface => $this->adapters->get($transaction?->connection),
            $configuration,
        );
        $connections = \array_map(static fn(AdapterInterface $adapter): string => $adapter->name, $adapters);
        
        if (\count(\array_unique($connections)) !== \count($adapters)) {
            throw new LogicException(\sprintf(
                'Attempted to start transaction more than once using same adapter ("%s").',
                \implode('", "', \array_keys(
                    \array_filter(
                        \array_count_values($connections),
                        static fn(int $count, string $name): bool => $count > 1,
                        \ARRAY_FILTER_USE_BOTH
                    )
                ))
            ));
        }

        for ($i = 0, $iMax = \count($configuration); $i < $iMax; $i++) {
            yield [$adapters[$i], $configuration[$i]];
        }
    }
}
