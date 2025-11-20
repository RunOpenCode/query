<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Last middleware in execution chain that actually executes query using appropriate
 * executor from registry.
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
        return $this->execute($query, $context, 'query');
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, ContextInterface $context, callable $next): int
    {
        return $this->execute($statement, $context, 'statement');
    }

    /**
     * @param non-empty-string    $query   Query or statement to execute.
     * @param ContextInterface    $context Middleware execution context.
     * @param 'query'|'statement' $method  Adapter method to invoke.
     *
     * @return ($method is 'query' ? ResultInterface : int)
     */
    private function execute(string $query, ContextInterface $context, string $method): ResultInterface|int
    {
        $adapter = $this->registry->get($context->peak(OptionsInterface::class)?->connection);
        /** @var OptionsInterface $options */
        $options    = $context->require(OptionsInterface::class) ?? $adapter->defaults(OptionsInterface::class);
        $parameters = $context->require(ParametersInterface::class);
        $scope      = $options->scope ?? ExecutionScope::Strict;
        $accepts    = null !== $context->transaction ? $context->transaction->accepts(...) : static fn(): true => true;

        if (!$accepts($adapter, $scope)) {
            throw new LogicException(\sprintf(
                'Execution of %s using connection "%s" within current transaction violates current execution scope configuration "%s".',
                $method,
                $adapter->name,
                $scope->name,
            ));
        }

        return $adapter->{$method}($query, $options, $parameters);
    }
}
