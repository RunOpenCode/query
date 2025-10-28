<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;

/**
 * Last middleware in execution chain that actually executes
 * query using appropriate executor from registry.
 *
 * Both methods, query and statement, will invoke the next
 * middleware in chain and assert that its return value is null, even
 * though contract does not allows it.
 *
 * This is done to ensure that there is no other middleware after
 * this one, as it is supposed to be the last one in chain.
 *
 * On top of that, the callable will check if context has been
 * correctly depleted by all previous middlewares, and throw
 * an exception if not.
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
        $options    = $context->require(OptionsInterface::class);
        $parameters = $context->require(ParametersInterface::class);
        $executor   = $this->registry->get($options?->connection);

        // @phpstan-ignore-next-line
        \assert(null === $next($query, $context));

        return $executor->query($query, $parameters, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ContextInterface $context, callable $next): int
    {
        $options    = $context->require(OptionsInterface::class);
        $parameters = $context->require(ParametersInterface::class);
        $executor   = $this->registry->get($options?->connection);

        // @phpstan-ignore-next-line
        \assert(null === $next($query, $context));

        return $executor->statement($query, $parameters, $options);
    }
}