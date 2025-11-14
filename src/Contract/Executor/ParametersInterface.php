<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Bag of query parameters.
 *
 * If adapter supports prepared statements, or any kind of query parameters,
 * they should be provided via parameters bag.
 *
 * @template TParamKey = non-empty-string|non-negative-int
 * @template TParamType = non-empty-string|\UnitEnum
 *
 * @phpstan-type Parameter = array{TParamKey, TParamType, mixed}
 *
 * @extends \IteratorAggregate<Parameter>
 */
interface ParametersInterface extends \IteratorAggregate, \Countable
{
    /**
     * @var array<TParamKey, mixed> Collection of parameter values.
     */
    public array $values {
        get;
    }

    /**
     * @var array<TParamKey, TParamType> Collection of parameter types.
     */
    public array $types {
        get;
    }
}
