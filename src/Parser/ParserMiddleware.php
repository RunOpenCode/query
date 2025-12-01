<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parser;

use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\StatementContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;

/**
 * Parser middleware.
 *
 * This middleware is responsible for parsing dynamic queries and statements
 * using language from parser registry.
 *
 * @phpstan-import-type Next from QueryMiddlewareInterface as NextQuery
 * @phpstan-import-type Next from StatementMiddlewareInterface as NextStatement
 */
final readonly class ParserMiddleware implements QueryMiddlewareInterface, StatementMiddlewareInterface
{
    public function __construct(
        private ParserRegistry $registry,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        return $next($this->parse($query, $context), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $statement, StatementContextInterface $context, callable $next): AffectedInterface
    {
        return $next($this->parse($statement, $context), $context);
    }

    /**
     * Parse query using parser from registry.
     *
     * @param non-empty-string $source  Query/statement to parse.
     * @param ContextInterface $context Current middleware context.
     *
     * @return non-empty-string Parsed query source.
     */
    private function parse(string $source, ContextInterface $context): string
    {
        /**
         * NOTE: We use peak here because parameters are consumed by parser,
         * but can not be considered as this middleware configuration.
         */
        $parameters = $context->peak(ParametersInterface::class);
        $variables  = $context->require(VariablesInterface::class);

        return $this->registry->parse($source, new ContextAwareVariables(
            $context,
            $variables,
            $parameters
        ));
    }
}
