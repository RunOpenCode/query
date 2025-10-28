<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Middleware;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

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
        $middlewares     = \is_array($this->middlewares) ? $this->middlewares : \iterator_to_array($this->middlewares, false);
        $reversed        = \array_values(\array_reverse($middlewares));
        $this->query     = $this->build($reversed, 'query');
        $this->statement = $this->build($reversed, 'statement');
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
    private function build(array $middlewares, string $method): callable
    {
        $last = self::last(...);

        foreach ($middlewares as $middleware) {
            // @phpstan-ignore-next-line
            $last = static fn(string $query, Context $context): ResultInterface|int => $middleware->{$method}($query, $context, $last);
        }
        // @phpstan-ignore-next-line
        return $last;
    }

    /**
     * Last, safe-guard middleware in chain.
     *
     * This last middleware will ensure that context is fully depleted when reached. If not,
     * it will throw LogicException indicating which configurations were left unused.
     *
     * @param non-empty-string $query
     * @param Context          $context
     *
     * @return null
     *
     * @throws LogicException When context is not depleted.
     */
    private static function last(string $query, Context $context): null
    {
        if ($context->depleted()) {
            return null;
        }

        $unused = \array_map(static fn(object $configuration): string => $configuration::class, \iterator_to_array($context->unused()));

        throw new LogicException(\sprintf(
            'Configurations from the context are not depleted, there are unused configurations: "%s".',
            \implode('", "', $unused),
        ));
    }
}
