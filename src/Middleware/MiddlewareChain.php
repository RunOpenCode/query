<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\ExecutorInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Middleware chain builder and executor.
 *
 * @phpstan-type Middleware = QueryMiddlewareInterface|StatementMiddlewareInterface|TransactionMiddlewareInterface
 *
 * @phpstan-import-type Next from QueryMiddlewareInterface as NextQuery
 * @phpstan-import-type Next from StatementMiddlewareInterface as NextStatement
 * @phpstan-import-type Next from TransactionMiddlewareInterface as NextTransactional
 */
final readonly class MiddlewareChain
{
    /**
     * @var \ArrayObject<class-string<Middleware>, NextQuery|NextStatement|NextTransactional>
     */
    private \ArrayObject $chains;

    /**
     * @param iterable<Middleware> $middlewares
     */
    public function __construct(
        private iterable $middlewares
    ) {
        $this->chains = new \ArrayObject();
    }

    /**
     * Execute query through middleware chain.
     *
     * @param non-empty-string      $query   Query to execute.
     * @param QueryContextInterface $context Middleware execution context.
     *
     * @return ResultInterface<array-key, mixed[]|object> Query result.
     */
    public function query(string $query, QueryContextInterface $context): ResultInterface
    {
        // @phpstan-ignore-next-line
        return ($this->get(QueryMiddlewareInterface::class))($query, $context);
    }

    /**
     * Execute statement through middleware chain.
     *
     * @param non-empty-string          $statement Statement to execute.
     * @param StatementContextInterface $context   Middleware execution context.
     *
     * @return AffectedInterface Report about affected database objects.
     */
    public function statement(string $statement, StatementContextInterface $context): AffectedInterface
    {
        // @phpstan-ignore-next-line
        return ($this->get(StatementMiddlewareInterface::class))($statement, $context);
    }

    /**
     * Execute transactional function through middleware chain.
     *
     * @template T of mixed = mixed
     *
     * @param callable(ExecutorInterface): T $function Function to be executed inside transactional scope.
     * @param TransactionContextInterface    $context  Middleware execution context.
     *
     * @return T
     */
    public function transactional(callable $function, TransactionContextInterface $context): mixed
    {
        return ($this->get(TransactionMiddlewareInterface::class))($function, $context);
    }

    /**
     * Get middleware chain.
     *
     * If middleware chain is not built, build it and store it
     * for successive calls.
     *
     * @param class-string<Middleware> $type Type of middleware chain to fetch.
     */
    private function get(string $type): callable
    {
        if (!$this->chains->offsetExists($type)) {
            $this->chains->offsetSet($type, $this->build($type));
        }

        // @phpstan-ignore-next-line
        return $this->chains->offsetGet($type);
    }

    /**
     * @param class-string<Middleware> $type Type of middleware chain to build.
     */
    private function build(string $type): callable
    {
        $last        = static fn(): never => throw new LogicException('Executor must not invoke next middleware in chain.');
        $middlewares = \array_reverse(\is_array($this->middlewares) ? $this->middlewares : \iterator_to_array($this->middlewares));
        $method      = match ($type) {
            QueryMiddlewareInterface::class => 'query',
            StatementMiddlewareInterface::class => 'statement',
            TransactionMiddlewareInterface::class => 'transactional',
            default => throw new LogicException(\sprintf(
                'Unknown middleware type "%s" provided.',
                $type
            ))
        };

        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof $type) {
                continue;
            }

            // @phpstan-ignore-next-line
            $last = static fn(string|callable $subject, ContextInterface $context): mixed => $middleware->{$method}($subject, $context, $last);
        }

        return $last;
    }
}
