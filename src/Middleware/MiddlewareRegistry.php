<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;

/**
 * Middleware registry and chain builder.
 *
 * @phpstan-import-type NextMiddlewareQueryCallable from MiddlewareInterface
 * @phpstan-import-type NextMiddlewareStatementCallable from MiddlewareInterface
 *
 * @internal
 */
final readonly class MiddlewareRegistry
{
    /**
     * Built query middleware chain.
     *
     * @var NextMiddlewareQueryCallable
     */
    private mixed $query;

    /**
     * Built statement middleware chain.
     *
     * @var NextMiddlewareStatementCallable
     */
    private mixed $statement;

    /**
     * @param iterable<MiddlewareInterface> $middlewares
     */
    public function __construct(
        private iterable $middlewares
    ) {
        $middlewares = \is_array($this->middlewares) ? $this->middlewares : \iterator_to_array($this->middlewares, false);
        $middlewares = \array_values(\array_reverse($middlewares));
        $executor    = \array_shift($middlewares);

        if (!$executor instanceof ExecutorMiddleware) {
            throw new LogicException(\sprintf(
                'Last middleware must be instance of %s, %s given.',
                ExecutorMiddleware::class,
                \get_debug_type($executor)
            ));
        }

        $this->query     = $this->build($executor, $middlewares, 'query');
        $this->statement = $this->build($executor, $middlewares, 'statement');
    }

    /**
     * Execute query through middleware chain.
     *
     * @param non-empty-string $query   Query to execute.
     * @param Context          $context Middleware execution context.
     *
     * @return ResultInterface
     */
    public function query(string $query, Context $context): ResultInterface
    {
        return ($this->query)($query, $context);
    }

    /**
     * Execute statement through middleware chain.
     *
     * @param non-empty-string $query   Query to execute.
     * @param Context          $context Middleware execution context.
     *
     * @return int Number of affected records.
     */
    public function statement(string $query, Context $context): int
    {
        return ($this->statement)($query, $context);
    }

    /**
     * Build middleware chain for given method.
     *
     * @param list<MiddlewareInterface> $middlewares Registered, reversed middlewares.
     * @param 'query'|'statement'       $method      Method to build chain for.
     *
     * @return ($method is 'query' ? NextMiddlewareQueryCallable : NextMiddlewareStatementCallable)
     */
    private function build(ExecutorMiddleware $executor, array $middlewares, string $method): callable
    {
        $last = static function(string $query, Context $context) use ($executor, $method): ResultInterface|int {
            // @phpstan-ignore-next-line
            $result = $executor->{$method}($query, $context, static fn(): never => throw new LogicException('Last middleware should not call next middleware.'));

            if (!$context->depleted()) {
                throw new LogicException(\sprintf(
                    'Unused execution middleware configurations detected: "%s". Did you forgot to register middleware in middleware chain, or it has been removed?',
                    \implode('", "', \array_map(
                        static fn(object $configuration): string => $configuration::class,
                        \iterator_to_array($context->unused())
                    )),
                ));
            }

            return $result;
        };

        foreach ($middlewares as $middleware) {
            // @phpstan-ignore-next-line
            $last = static fn(string $query, Context $context): ResultInterface|int => $middleware->{$method}($query, $context, $last);
        }

        // @phpstan-ignore-next-line
        return $last;
    }
}
