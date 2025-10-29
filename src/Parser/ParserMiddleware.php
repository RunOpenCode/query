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
 * This middleware is responsible for parsing dynamic query
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

        $parsed = $this->registry->parse(
            $query,
            $this->vars($context, $variables, $parameters),
            $variables?->parser,
        );

        return $next($parsed, $context);
    }

    /**
     * Prepare variables for parser.
     *
     * @param ContextInterface        $context    Current middleware context.
     * @param VariablesInterface|null $variables  Variables bag.
     * @param Parameters|null         $parameters Parameters bag.
     *
     * @return array<non-empty-string, mixed>
     */
    private function vars(
        ContextInterface     $context,
        ?VariablesInterface  $variables,
        ?ParametersInterface $parameters
    ): array {
        $vars   = null !== $variables ? \iterator_to_array($variables) : [];
        $params = null !== $parameters ? $parameters->values : [];

        // Ensure both are arrays with string keys
        if (\array_is_list($params)) {
            $params = [];
        }

        /** @var array<non-empty-string, mixed> $params */
        return \array_merge(
            $vars,
            $params,
            [
                'context' => [
                    'variables'  => $variables,
                    'parameters' => $parameters,
                    'middleware' => $context,
                ],
            ]
        );
    }
}
