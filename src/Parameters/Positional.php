<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parameters;

use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;

/**
 * Positional parameters bag.
 *
 * Do note that positional parameters are 0-based indexed, and
 * every removal of parameter will cause reindexing of the
 * remaining parameters.
 *
 * @implements ParametersInterface<non-negative-int>
 */
final class Positional implements ParametersInterface
{
    /**
     * {@inheritdoc}
     */
    public array $values {
        get => \array_map(
            static fn(Parameter $parameter): mixed => $parameter->value,
            $this->parameters
        );
    }

    /**
     * {@inheritdoc}
     */
    public array $types {
        get => \array_map(
            static fn(Parameter $parameter): mixed => $parameter->type,
            $this->parameters
        );
    }

    /**
     * Creates new instance of parameters bag.
     *
     * @param list<Parameter> $parameters
     */
    public function __construct(
        private array $parameters = [],
    ) {
        // noop
    }

    /**
     * Add new parameter to the bag.
     *
     * @param mixed $value Parameter value.
     * @param mixed $type  Optional parameter type.
     */
    public function add(mixed $value, mixed $type = null): self
    {
        $this->parameters[] = $value instanceof Parameter ? $value : new Parameter($value, $type);

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given offset already exists it will be overwritten.
     *
     * @param non-negative-int $offset Parameter offset.
     * @param mixed            $value  Parameter value.
     * @param mixed            $type   Optional parameter type.
     *
     * @throws OutOfBoundsException If trying to set parameter at offset greater than current maximum offset.
     */
    public function set(int $offset, mixed $value, mixed $type = null): self
    {
        if ($offset > \count($this->parameters)) {
            throw new OutOfBoundsException(\sprintf(
                'Cannot set parameter at offset "%s". Maximum allowed offset is "%d".',
                $offset,
                \count($this->parameters)
            ));
        }

        // @phpstan-ignore-next-line
        $this->parameters[$offset] = $value instanceof Parameter ? $value : new Parameter($value, $type);

        return $this;
    }

    /**
     * Remove parameter from the bag.
     *
     * If parameter with given offset does not exist, no action is performed.
     * Removal will cause reindexing of the remaining parameters.
     *
     * @param non-negative-int $offset Parameter offset.
     */
    public function remove(int $offset): self
    {
        // @phpstan-ignore-next-line
        unset($this->parameters[$offset]);

        $this->parameters = \array_values($this->parameters);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param non-negative-int $offset Parameter offset.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->parameters[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param non-negative-int $offset Parameter offset.
     *
     * @throws OutOfBoundsException If parameter with given offset does not exist.
     */
    public function offsetGet(mixed $offset): Parameter
    {
        return $this->parameters[$offset] ?? throw new OutOfBoundsException(\sprintf(
            'Parameter with offset "%s" does not exist.',
            $offset
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-negative-int $offset Parameter offset.
     * @param Parameter|mixed  $value  Parameter value. If value is not instance of Parameter it will be wrapped into one.
     *
     * @throws OutOfBoundsException If trying to set parameter at offset greater than current maximum offset.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Parameter) {
            $value = new Parameter($value);
        }

        if ($offset > \count($this->parameters)) {
            throw new OutOfBoundsException(\sprintf(
                'Cannot set parameter at offset "%s". Maximum allowed offset is "%d".',
                $offset,
                \count($this->parameters)
            ));
        }

        // @phpstan-ignore-next-line
        $this->parameters[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param non-negative-int $offset Parameter offset.
     */
    public function offsetUnset(mixed $offset): void
    {
        // @phpstan-ignore-next-line
        unset($this->parameters[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->parameters);
    }
}
