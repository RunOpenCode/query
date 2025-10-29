<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Parameters;

use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;

/**
 * Named parameters bag.
 *
 * @implements ParametersInterface<non-empty-string>
 */
final class Named implements ParametersInterface
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
     * @param array<non-empty-string, Parameter> $parameters
     */
    public function __construct(
        private array $parameters = [],
    ) {
        // noop
    }

    /**
     * Add new parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param mixed            $value Parameter value.
     * @param mixed            $type  Optional parameter type.
     *
     * @throws LogicException If parameter with given name already exists.
     */
    public function add(string $name, mixed $value, mixed $type = null): self
    {
        if (\array_key_exists($name, $this->parameters)) {
            throw new LogicException(\sprintf(
                'Cannot add parameter "%s" to parameters bag. Parameter with the same name already exists.',
                $name,
            ));
        }

        $this->parameters[$name] = $value instanceof Parameter ? $value : new Parameter($value, $type);

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given name already exists it will be overwritten.
     *
     * @param non-empty-string $name  Parameter name.
     * @param mixed            $value Parameter value.
     * @param mixed            $type  Optional parameter type.
     */
    public function set(string $name, mixed $value, mixed $type = null): self
    {
        $this->parameters[$name] = $value instanceof Parameter ? $value : new Parameter($value, $type);

        return $this;
    }

    /**
     * Remove parameter from the bag.
     *
     * If parameter with given name does not exist, no action is performed.
     *
     * @param non-empty-string $name Parameter name.
     */
    public function remove(string $name): self
    {
        unset($this->parameters[$name]);

        return $this;
    }

    /**
     * Merge another parameters bag into this one.
     *
     * @param ParametersInterface<non-empty-string> $parameters Parameters bag to merge from.
     * @param bool                                  $overwrite  If set to true existing parameters will be overwritten.
     */
    public function merge(ParametersInterface $parameters, bool $overwrite = true): self
    {
        foreach ($parameters as $name => $parameter) {
            if ($overwrite) {
                $this->parameters[$name] = $parameter;
                continue;
            }

            $this->add($name, $parameter->value, $parameter->type);
        }

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
     * @param non-empty-string $offset Parameter name.
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->parameters[$offset]);
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $offset Parameter name.
     *
     * @throws OutOfBoundsException If parameter with given name does not exist.
     */
    public function offsetGet(mixed $offset): Parameter
    {
        return $this->parameters[$offset] ?? throw new OutOfBoundsException(\sprintf(
            'Parameter with name "%s" does not exist.',
            $offset
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $offset Parameter name.
     * @param Parameter|mixed  $value  Parameter value. If value is not instance of Parameter it will be wrapped into one.
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Parameter) {
            $value = new Parameter($value);
        }

        $this->parameters[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param non-empty-string $offset Parameter name.
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->parameters[$offset]);
    }

    public function count(): int
    {
        return \count($this->parameters);
    }
}
