<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Middleware;

use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;

/**
 * Middleware for statement execution.
 *
 * @phpstan-type Next = callable(string, StatementContextInterface): AffectedInterface
 */
interface StatementMiddlewareInterface
{
    /**
     * Invoke middleware for statement.
     *
     * @param non-empty-string          $statement Statement to execute.
     * @param StatementContextInterface $context   Statement execution context.
     * @param Next                      $next      Next middleware to call.
     *
     * @return AffectedInterface Report about affected objects.
     */
    public function statement(string $statement, StatementContextInterface $context, callable $next): AffectedInterface;
}
