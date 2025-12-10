<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;

use function RunOpenCode\Component\Query\enum_to_scalar;
use function RunOpenCode\Component\Query\to_date_time_immutable;

/**
 * Positional parameters bag.
 *
 * Do note that positional parameters are 0-based indexed, and
 * every removal of parameter will cause reindexing of the
 * remaining parameters.
 *
 * @phpstan-type DbalParameterType = ArrayParameterType|ParameterType|non-empty-string|null
 *
 * @implements ParametersInterface<non-negative-int, DbalParameterType>
 */
final class Positional implements ParametersInterface
{
    /**
     * {@inheritdoc}
     */
    public array $values {
        get => \array_map(
            static fn(array $param): mixed => $param[1],
            $this->parameters,
        );
    }

    /**
     * {@inheritdoc}
     */
    public array $types {
        get => \array_map(
            static fn(array $param): mixed => $param[0],
            $this->parameters
        );
    }

    /**
     * @var list<array{DbalParameterType, mixed}>
     */
    private array $parameters = [];

    /**
     * Creates new instance of positional parameters bag.
     */
    public function __construct()
    {
        // noop
    }

    /**
     * Add new parameter to the bag.
     *
     * @param mixed             $value Parameter value.
     * @param DbalParameterType $type  Optional parameter type.
     */
    public function add(mixed $value, ArrayParameterType|ParameterType|string|null $type = null): self
    {
        $this->parameters[] = [$type, $value];

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given offset already exists it will be overwritten.
     *
     * @param non-negative-int  $offset Parameter offset.
     * @param mixed             $value  Parameter value.
     * @param DbalParameterType $type   Optional parameter type.
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

        $this->parameters[$offset] = [$type, $value];

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
     * Merge another parameters bag into this one.
     *
     * All values will be appended to this parameter bag, offsets/names will be ignored.
     *
     * @param ParametersInterface<non-negative-int, DbalParameterType>|ParametersInterface<non-empty-string, DbalParameterType> $parameters Parameters bag to merge from.
     */
    public function merge(ParametersInterface $parameters): self
    {
        foreach ($parameters as [, $type, $value]) {
            $this->add($value, $type);
        }

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
        return $this->add(to_date_time_immutable($value), Types::DATE_IMMUTABLE);
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
        return $this->add(to_date_time_immutable($value), Types::DATETIME_IMMUTABLE);
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
        return $this->add(to_date_time_immutable($value), Types::DATETIMETZ_IMMUTABLE);
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
        return $this->add(to_date_time_immutable($value), Types::TIME_IMMUTABLE);
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
     */
    public function enum(?\UnitEnum $value): self
    {
        $extracted = enum_to_scalar($value);

        match (true) {
            null === $extracted => $this->add(null),
            \is_string($extracted) => $this->string($extracted),
            \is_int($extracted) => $this->integer($extracted),
        };

        return $this;
    }

    /**
     * Add parameter value as array of integers.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param ?iterable<int> $value Parameter value.
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
     */
    public function enumArray(?iterable $value): self
    {
        if (null === $value) {
            return $this->add(null);
        }

        $values    = [];
        $hasString = false;

        foreach ($value as $current) {
            $extracted = enum_to_scalar($current);
            $hasString = $hasString || \is_string($extracted);
            $values[]  = $extracted;
        }

        if (0 === \count($values)) {
            return $this->add(null);
        }

        // @phpstan-ignore-next-line
        return $hasString ? $this->stringArray($values) : $this->integerArray($values);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->parameters as $name => [$type, $value]) {
            yield [$name, $type, $value];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->parameters);
    }
}
