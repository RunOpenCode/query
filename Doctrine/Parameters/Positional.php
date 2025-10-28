<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;
use RunOpenCode\Component\Query\Parameters\Positional as PositionalParametersBag;
use RunOpenCode\Component\Query\Parameters\Parameter;

/**
 * Named parameters bag for Doctrine queries.
 *
 * @implements ParametersInterface<non-negative-int>
 */
final class Positional implements ParametersInterface
{
    /**
     * {@inheritdoc}
     */
    public array $values {
        get => $this->bag->values;
    }

    /**
     * {@inheritdoc}
     */
    public array $types {
        get => $this->bag->types;
    }

    private readonly PositionalParametersBag $bag;

    /**
     * Creates new instance of named parameters bag for Doctrine queries.
     *
     * @param list<Parameter> $parameters
     */
    public function __construct(
        array $parameters = [],
    ) {
        $this->bag = new PositionalParametersBag($parameters);
    }

    /**
     * Add new parameter to the bag.
     *
     * @param mixed                                    $value Parameter value.
     * @param ArrayParameterType|non-empty-string|null $type  Optional parameter type.
     */
    public function add(mixed $value, ArrayParameterType|string|null $type = null): self
    {
        $this->bag->add($value, $type);

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given offset already exists it will be overwritten.
     *
     * @param non-negative-int                         $offset Parameter offset.
     * @param mixed                                    $value  Parameter value.
     * @param ArrayParameterType|non-empty-string|null $type   Optional parameter type.
     *
     * @throws OutOfBoundsException If trying to set parameter at offset greater than current maximum offset.
     */
    public function set(int $offset, mixed $value, ArrayParameterType|string|null $type = null): self
    {
        $this->bag->set($offset, $value, $type);

        return $this;
    }

    /**
     * Removes parameter from the bag.
     *
     * If parameter with given offset does not exist, no action is performed.
     * Removal will cause reindexing of the remaining parameters.
     *
     * @param non-negative-int $offset Parameter offset.
     */
    public function remove(int $offset): self
    {
        $this->bag->remove($offset);

        return $this;
    }

    /**
     * Add ASCII string parameter to the bag.
     *
     * @param \Stringable|string|null $value Parameter value.
     */
    public function asciiString(\Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->add($value, Types::ASCII_STRING);
    }

    /**
     * Add big integer parameter to the bag.
     *
     * @param int|null $value Parameter value.
     */
    public function bigint(?int $value): self
    {
        return $this->add($value, Types::BIGINT);
    }

    /**
     * Add binary parameter to the bag.
     *
     * @param mixed $value Parameter value.
     */
    public function binary(mixed $value): self
    {
        return $this->add($value, Types::BINARY);
    }

    /**
     * Add blob parameter to the bag.
     *
     * @param mixed $value Parameter value.
     */
    public function blob(mixed $value): self
    {
        return $this->add($value, Types::BLOB);
    }

    /**
     * Add boolean parameter to the bag.
     *
     * @param bool|null $value Parameter value.
     */
    public function boolean(?bool $value): self
    {
        return $this->add($value, Types::BOOLEAN);
    }

    /**
     * Add date parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function date(?\DateTimeInterface $value): self
    {
        return $this->add($value, Types::DATE_MUTABLE);
    }

    /**
     * Add date immutable parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateImmutable(?\DateTimeInterface $value): self
    {
        return $this->add($value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATE_IMMUTABLE);
    }

    /**
     * Add date interval parameter to the bag.
     *
     * @param \DateInterval|null $value Parameter value.
     */
    public function dateInterval(?\DateInterval $value): self
    {
        return $this->add($value, Types::DATEINTERVAL);
    }

    /**
     * Add datetime parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTime(?\DateTimeInterface $value): self
    {
        return $this->add($value, Types::DATETIME_MUTABLE);
    }

    /**
     * Add datetime immutable parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeImmutable(?\DateTimeInterface $value): self
    {
        return $this->add($value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATETIME_IMMUTABLE);
    }

    /**
     * Add datetime with timezone parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeTz(?\DateTimeInterface $value): self
    {
        return $this->add($value, Types::DATETIMETZ_MUTABLE);
    }

    /**
     * Add datetime with timezone immutable parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeTzImmutable(?\DateTimeInterface $value): self
    {
        return $this->add($value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATETIMETZ_IMMUTABLE);
    }

    /**
     * Add decimal parameter to the bag.
     *
     * @param float|null $value Parameter value.
     */
    public function decimal(?float $value): self
    {
        return $this->add($value, Types::DECIMAL);
    }

    /**
     * Add float parameter to the bag.
     *
     * @param float|null $value Parameter value.
     */
    public function float(?float $value): self
    {
        return $this->add($value, Types::FLOAT);
    }

    /**
     * Add GUID parameter to the bag.
     *
     * @param \Stringable|string|null $value Parameter value.
     */
    public function guid(\Stringable|string|null $value): self
    {
        return $this->add($value, Types::GUID);
    }

    /**
     * Add integer parameter to the bag.
     *
     * @param int|null $value Parameter value.
     */
    public function integer(?int $value): self
    {
        return $this->add($value, Types::INTEGER);
    }

    /**
     * Add JSON parameter to the bag.
     *
     * @param mixed $value Parameter value.
     */
    public function json(mixed $value): self
    {
        return $this->add($value, Types::JSON);
    }

    /**
     * Add simple array parameter to the bag.
     *
     * @param scalar[]|null $value Parameter value.
     */
    public function simpleArray(?array $value): self
    {
        return $this->add($value, Types::SIMPLE_ARRAY);
    }

    /**
     * Add small integer parameter to the bag.
     *
     * @param int|null $value Parameter value.
     */
    public function smallInt(?int $value): self
    {
        return $this->add($value, Types::SMALLINT);
    }

    /**
     * Add string parameter to the bag.
     *
     * @param \Stringable|string|null $value Parameter value.
     */
    public function string(\Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->add($value, Types::STRING);
    }

    /**
     * Add text parameter to the bag.
     *
     * @param \Stringable|string|null $value Parameter value.
     */
    public function text(\Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->add($value, Types::TEXT);
    }

    /**
     * Add time parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function time(?\DateTimeInterface $value): self
    {
        return $this->add($value, Types::TIME_MUTABLE);
    }

    /**
     * Add time immutable parameter to the bag.
     *
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function timeImmutable(?\DateTimeInterface $value): self
    {
        return $this->add($value ? \DateTimeImmutable::createFromInterface($value) : null, Types::TIME_IMMUTABLE);
    }

    /**
     * Add parameter value as enum.
     *
     * For backed enums, the value will be converted to the backing type, which means
     * that either string or integer parameter will be set.
     *
     * For non-backed enums, the value will be extracted from the enum name, which means
     * that the parameter will be set as string.
     *
     * In general, this is shorthand for:
     *
     * ```php
     * // Backed enums
     * $parameters->add($value->value, Types::STRING);
     * $parameters->add($value->value, Types::INTEGER);
     * // Non-backed enums
     * $parameters->add($value->name, Types::STRING);
     * ```
     *
     * @param ?\UnitEnum $value Parameter value.
     *
     * @return self
     */
    public function enum(?\UnitEnum $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $reflection = new \ReflectionEnum($value::class);

        if (!$reflection->isBacked()) {
            return $this->string($value->name);
        }

        /** @var \BackedEnum $value */
        return \is_string($value->value) ? $this->string($value->value) : $this->integer($value->value);
    }

    /**
     * Add parameter value as array of integers.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param ?iterable<int> $value Parameter value.
     *
     * @return self
     */
    public function integerArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->add(null);
        }

        return $this->add(\array_values($value), ArrayParameterType::INTEGER);
    }

    /**
     * Add parameter value as array of strings.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param ?iterable<\Stringable|string> $value Parameter value.
     *
     * @return self
     */
    public function stringArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->add(null);
        }

        return $this->add(
            \array_map('\strval', \array_values($value)),
            ArrayParameterType::STRING
        );
    }

    /**
     * Add parameter value as array of ascii strings.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param ?iterable<\Stringable|string> $value Parameter value.
     *
     * @return self
     */
    public function asciiArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->add(null);
        }

        return $this->add(
            \array_map('\strval', \array_values($value)),
            ArrayParameterType::ASCII
        );
    }

    /**
     * Add parameter value as array of binary values.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param ?iterable<mixed> $value Parameter value.
     *
     * @return self
     */
    public function binaryArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->add(null);
        }

        return $this->add(\array_values($value), ArrayParameterType::BINARY);
    }

    /**
     * Add parameter value as array of enum values.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * You may used mixed types of enums in the array, but it is not recommended. If only
     * integer backed enums are used, the parameter will be set as integer array. Otherwise,
     * the parameter will be set as string array.
     *
     * @param ?iterable<\UnitEnum> $value Parameter value.
     *
     * @return self
     */
    public function enumArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->add(null);
        }

        $hasString = false;
        $values    = [];

        foreach ($value as $item) {
            $reflection = new \ReflectionEnum($item);

            if (!$reflection->isBacked()) {
                $values[]  = $item->name;
                $hasString = true;
                continue;
            }

            /** @var \BackedEnum $item */
            $values[]  = $item->value;
            $hasString = $hasString || \is_string($item->value);
        }

        // @phpstan-ignore-next-line
        return $hasString ? $this->stringArray($values) : $this->integerArray($values);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return $this->bag->{__FUNCTION__}(...\func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists(mixed $offset): bool
    {
        // @phpstan-ignore-next-line
        return $this->bag->{__FUNCTION__}(...\func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet(mixed $offset): Parameter
    {
        // @phpstan-ignore-next-line
        return $this->bag->{__FUNCTION__}(...\func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        // @phpstan-ignore-next-line
        $this->bag->{__FUNCTION__}(...\func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset(mixed $offset): void
    {
        // @phpstan-ignore-next-line
        $this->bag->{__FUNCTION__}(...\func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->bag->{__FUNCTION__}(...\func_get_args());
    }
}