<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Configuration\TransactionInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;

/**
 * @internal
 */
final class ContextFactory
{
    /**
     * @var \WeakMap<AdapterRegistry, ContextFactory>
     */
    private static \WeakMap $instances;

    private function __construct(private readonly AdapterRegistry $registry)
    {
        // noop.
    }

    /**
     * Get instance of the context factory for given adapter registry.
     *
     * @param AdapterRegistry $registry Registry for which context factory should be provided.
     *
     * @return self Instance of context factory.
     */
    public static function instance(AdapterRegistry $registry): self
    {
        if (!isset(self::$instances)) {
            self::$instances = new \WeakMap();
        }

        if (!self::$instances->offsetExists($registry)) {
            self::$instances->offsetSet($registry, new self($registry));
        }

        return self::$instances->offsetGet($registry);
    }

    /**
     * Create query context.
     *
     * @param non-empty-string                 $query            Query being executed.
     * @param TransactionContextInterface|null $context          Current transaction context.
     * @param object                           ...$configuration Provided configurations.
     */
    public function query(string $query, ?TransactionContextInterface $context, object ...$configuration): QueryContextInterface
    {
        /**
         * @var list<ExecutionInterface>   $execution
         * @var list<TransactionInterface> $transaction
         * @var list<object>               $middleware
         */
        [$execution, $transaction, $middleware] = $this->extract(
            $configuration,
            ExecutionInterface::class,
            TransactionInterface::class
        );

        if (!empty($transaction)) {
            throw new InvalidArgumentException('You may not provide transaction configuration in context of query execution.');
        }

        if (\count($execution) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Only one execution configuration for query may be provided, %d given.',
                \count($execution)
            ));
        }

        /** @var ExecutionInterface $execution */
        $execution = $execution[0] ?? $this->registry->get()->defaults(ExecutionInterface::class);

        if (null === $execution->connection) {
            $execution = $execution->withConnection($this->registry->default);
        }

        return new QueryContext($query, $execution, $context, ...$middleware);
    }

    /**
     * Create statement context.
     *
     * @param non-empty-string                 $statement        Statement being executed.
     * @param TransactionContextInterface|null $context          Current transaction context.
     * @param object                           ...$configuration Provided configurations.
     */
    public function statement(string $statement, ?TransactionContextInterface $context, object ...$configuration): StatementContextInterface
    {
        /**
         * @var list<ExecutionInterface>   $execution
         * @var list<TransactionInterface> $transaction
         * @var list<object>               $middleware
         */
        [$execution, $transaction, $middleware] = $this->extract(
            $configuration,
            ExecutionInterface::class,
            TransactionInterface::class
        );

        if (!empty($transaction)) {
            throw new InvalidArgumentException('You may not provide transaction configuration in context of statement execution.');
        }

        if (\count($execution) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Only one execution configuration for statement may be provided, %d given.',
                \count($execution)
            ));
        }

        /** @var ExecutionInterface $execution */
        $execution = $execution[0] ?? $this->registry->get()->defaults(ExecutionInterface::class);

        if (null === $execution->connection) {
            $execution = $execution->withConnection($this->registry->default);
        }

        return new StatementContext($statement, $execution, $context, ...$middleware);
    }

    public function transaction(?TransactionContextInterface $context, object ...$configuration): TransactionContextInterface
    {
        /**
         * @var list<TransactionInterface> $transaction
         * @var list<ExecutionInterface>   $execution
         * @var list<ParametersInterface>  $parameters
         * @var list<VariablesInterface>   $variables
         * @var list<object>               $middleware
         */
        [$transaction, $execution, $parameters, $variables, $middleware] = $this->extract(
            $configuration,
            TransactionInterface::class,
            ExecutionInterface::class,
            ParametersInterface::class,
            VariablesInterface::class,
        );

        if (!empty($execution)) {
            throw new InvalidArgumentException('You may not provide execution configuration in context of transaction execution.');
        }

        if (!empty($parameters)) {
            throw new InvalidArgumentException('You may not provide parameters for execution adapter in context of transaction execution.');
        }

        if (!empty($variables)) {
            throw new InvalidArgumentException('You may not provide variables for parser in context of transaction execution.');
        }

        if (empty($transaction)) {
            $transaction[] = $this->registry->get()->defaults(TransactionInterface::class);
        }

        $transaction = \array_map(
            fn(TransactionInterface $current): TransactionInterface => null !== $current->connection ? $current : $current->withConnection($this->registry->default),
            $transaction
        );

        return new TransactionContext($transaction, $context, ...$middleware);
    }

    /**
     * Extract configuration objects by type, where non matching
     * types are assumed to be configuration for the middlewares.
     *
     * @param object[]             $configuration
     * @param class-string<object> ...$types
     *
     * @return list<object[]>
     */
    private function extract(array $configuration, string ...$types): array
    {
        $extracted  = \array_combine(
            \array_values($types),
            \array_fill(0, \count($types), []),
        );
        $middleware = [];

        foreach ($configuration as $current) {
            foreach ($types as $type) {
                if ($current instanceof $type) {
                    $extracted[$type][] = $current;
                    continue 2;
                }
            }

            $middleware[] = $current;
        }

        return [
            ...\array_values($extracted),
            $middleware,
        ];
    }
}
