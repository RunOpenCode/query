<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Last middleware in execution chain that actually executes query using appropriate
 * executor from registry.
 *
 * Both methods, query and statement, will invoke the next middleware in chain and
 * assert that its return value is null, even though contract does not allows it.
 *
 * This is done to ensure that there is no other middleware after this one, as it
 * is supposed to be the last one in chain.
 *
 * On top of that, the callable will check if context has been correctly depleted by
 * all previous middlewares, and throw an exception if not.
 *
 * @phpstan-import-type NextMiddlewareQueryCallable from MiddlewareInterface
 * @phpstan-import-type NextMiddlewareStatementCallable from MiddlewareInterface
 *
 * @phpstan-type NextMiddlewareCallable = NextMiddlewareQueryCallable|NextMiddlewareStatementCallable
 *
 * @internal
 */
final readonly class ExecutorMiddleware implements MiddlewareInterface
{
    public function __construct(private AdapterRegistry $registry)
    {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, ContextInterface $context, callable $next): ResultInterface
    {
        return $this->execute($query, $context, $next, 'query');
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ContextInterface $context, callable $next): int
    {
        return $this->execute($query, $context, $next, 'statement');
    }

    /**
     * @param non-empty-string       $query   Query or statement to execute.
     * @param ContextInterface       $context Middleware execution context.
     * @param NextMiddlewareCallable $next    Next middleware to call and verify that this is last middleware in chain.
     * @param 'query'|'statement'    $method  Adapter method to invoke.
     *
     * @return ($method is 'query' ? ResultInterface : int)
     */
    private function execute(string $query, ContextInterface $context, callable $next, string $method): ResultInterface|int
    {
        $options    = $context->require(OptionsInterface::class);
        $parameters = $context->require(ParametersInterface::class);
        $adapter    = $this->registry->get($options?->connection);

        if (!$context->depleted()) {
            throw new LogicException(\sprintf(
                'Unused execution middleware configurations detected: "%s". Did you forgot to register middleware in middleware chain, or it has been removed?',
                \implode('", "', \array_map(
                    static fn(object $configuration): string => $configuration::class,
                    \iterator_to_array($context->unused())
                )),
            ));
        }

        // @phpstan-ignore-next-line
        if (null === $next($query, $context)) {
            throw new LogicException(\sprintf(
                'Middleware "%s" is expected to be last in middleware chain, next detected. Did you misconfigured middlewares?',
                self::class
            ));
        }

        return $adapter->{$method}($query, $parameters, $options);
    }
}
