<?php

declare(strict_types=1);

namespace RunOpenCode\Bundle\Query\Contract\Parser;

use RunOpenCode\Component\Query\Exception\LogicException;

/**
 * Bag of variables.
 *
 * Bag of variables is object containing variables which are used during query processing
 * and parsing phase, but are not used during query execution.
 *
 * @extends \IteratorAggregate<non-empty-string, mixed>
 * @extends \ArrayAccess<non-empty-string, mixed>
 */
interface VariablesInterface extends \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * Gets parser which should be used to parse query
     * using these variables. If no parser is set, default
     * parser should be used.
     * 
     * @var non-empty-string|null
     */
    public ?string $parser {
        get;
    }

    /**
     * Adds variable to the bag.
     *
     * Throws exception if variable with the same name already exists. Use
     * set() method to override existing variable.
     *
     * @param non-empty-string $name  Name of the variable.
     * @param mixed            $value Value of the variable.
     *
     * @throws LogicException If variable with the same name already exists.
     */
    public function add(string $name, mixed $value): VariablesInterface;

    /**
     * Sets variable in the bag.
     *
     * @param non-empty-string $name  Name of the variable.
     * @param mixed            $value Value of the variable.
     */
    public function set(string $name, mixed $value): VariablesInterface;

    /**
     * Removes variable from the bag.
     *
     * Existence of the variable is not checked.
     *
     * @param non-empty-string $name Name of the variable.
     */
    public function remove(string $name): VariablesInterface;

    /**
     * Merges another variables bag or array of variables into this bag.
     *
     * @param VariablesInterface|array<non-empty-string, mixed> $other     Other variables bag or array to merge from.
     * @param bool                                              $overwrite Whether existing variables should be overwritten.
     *
     * @throws LogicException If $overwrite is false and variable with the same name already exists.
     */
    public function merge(VariablesInterface|array $other, bool $overwrite = true): VariablesInterface;
}
