<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

use RunOpenCode\Component\Query\Parameters\Parameter;

/**
 * Bag of query parameters.
 *
 * @phpstan-type NamedParameters = ParametersInterface<non-empty-string>
 * @phpstan-type PositionalParameters = ParametersInterface<non-negative-int>
 * @phpstan-type Parameters = NamedParameters|PositionalParameters
 *
 * @template TKey of non-empty-string|non-negative-int
 *
 * @extends \IteratorAggregate<TKey, Parameter>
 * @extends \ArrayAccess<TKey, Parameter>
 */
interface ParametersInterface extends \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<TKey, mixed> Collection of parameter values.
     */
    public array $values {
        get;
    }

    /**
     * @var array<TKey, mixed> Collection of parameter types.
     */
    public array $types {
        get;
    }
}
