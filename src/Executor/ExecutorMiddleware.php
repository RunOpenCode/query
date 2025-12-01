<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Executor;

use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\MiddlewaresConfiguration;

/**
 * Executor middleware.
 *
 * Last middleware in execution chain that actually executes query using appropriate
 * executor from registry.
 */
final readonly class ExecutorMiddleware implements QueryMiddlewareInterface, StatementMiddlewareInterface, TransactionMiddlewareInterface
{
    public function __construct(private AdapterRegistry $registry)
    {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        try {
            return $this->execute($query, $context, 'query');
        } finally {
            \assert($this->close($context) ?: true); // @phpstan-ignore-line
        }
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, StatementContextInterface $context, callable $next): AffectedInterface
    {
        try {
            return $this->execute($statement, $context, 'statement');
        } finally {
            \assert($this->close($context) ?: true); // @phpstan-ignore-line
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $function, TransactionContextInterface $context, callable $next): mixed
    {
        try {
            return $function();
        } finally {
            \assert($this->close($context) ?: true); // @phpstan-ignore-line
        }
    }

    /**
     * @param non-empty-string                                $query   Query or statement to execute.
     * @param StatementContextInterface|QueryContextInterface $context Middleware query or statement execution context.
     * @param 'query'|'statement'                             $method  Adapter method to invoke.
     *
     * @return ($method is 'query' ? ResultInterface : AffectedInterface)
     */
    private function execute(string $query, StatementContextInterface|QueryContextInterface $context, string $method): ResultInterface|AffectedInterface
    {
        $adapter    = $this->registry->get($context->execution->connection);
        $parameters = $context->require(ParametersInterface::class);
        $scope      = $context->execution->scope ?? ExecutionScope::Strict;

        if (null !== $context->transaction && !$context->transaction->accepts($scope, $adapter->name)) {
            throw new LogicException(\sprintf(
                'Execution of %s using connection "%s" within current transaction violates current execution scope configuration "%s".',
                $method,
                $adapter->name,
                $scope->name,
            ));
        }

        return $adapter->{$method}($query, $context->execution, $parameters);
    }

    private function close(ContextInterface $context): void
    {
        if ($context->middlewares instanceof MiddlewaresConfiguration) {
            $context->middlewares->exhaust();
        }
    }
}
