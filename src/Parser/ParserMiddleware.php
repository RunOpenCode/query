<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;

/**
 * Parser middleware.
 *
 * This middleware is responsible for parsing dynamic queries and statements
 * using language from parser registry.
 *
 * @phpstan-import-type Parameters from ParametersInterface
 * @phpstan-import-type NextMiddlewareQueryCallable from MiddlewareInterface
 * @phpstan-import-type NextMiddlewareStatementCallable from MiddlewareInterface
 *
 * @internal
 */
final readonly class ParserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ParserRegistry $registry,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, ContextInterface $context, callable $next): ResultInterface
    {
        return $this->parse($query, $context, $next);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ContextInterface $context, callable $next): int
    {
        return $this->parse($query, $context, $next);
    }

    /**
     * Parse query using parser from registry.
     *
     * @param non-empty-string                                            $query   Query to parse.
     * @param ContextInterface                                            $context Current middleware context.
     * @param NextMiddlewareQueryCallable|NextMiddlewareStatementCallable $next    Next middleware to call.
     *
     * @return ($next is NextMiddlewareQueryCallable ? ResultInterface : int) Result of execution.
     */
    private function parse(string $query, ContextInterface $context, callable $next): ResultInterface|int
    {
        /**
         * @var Parameters|null $parameters
         *
         * NOTE: We use peak here because parameters are consumed by parser,
         * but can not be considered as this middleware configuration.
         */
        $parameters = $context->peak(ParametersInterface::class);
        $variables  = $context->require(VariablesInterface::class);
        $parsed     = $this->registry->parse($query, new ContextAwareVariables(
            $context,
            $variables,
            $parameters
        ));

        return $next($parsed, $context);
    }
}
