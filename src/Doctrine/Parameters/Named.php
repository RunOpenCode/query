<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

use function RunOpenCode\Component\Query\enum_value;
use function RunOpenCode\Component\Query\to_date_time_immutable;

/**
 * Named parameters bag for Doctrine queries.
 *
 * @phpstan-type DbalParameterType = ArrayParameterType|ParameterType|non-empty-string|null
 *
 * @implements ParametersInterface<non-empty-string, DbalParameterType>
 */
final class Named implements ParametersInterface
{
    /**
     * {@inheritdoc}
     */
    public array $values {
        get => \array_map(
            static fn(array $param): mixed => $param[1],
            $this->parameters
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
     * @var array<non-empty-string, array{DbalParameterType, mixed}>
     */
    private array $parameters = [];

    /**
     * Creates new instance of named parameters bag.
     */
    public function __construct()
    {
        // noop
    }

    /**
     * Add new parameter to the bag.
     *
     * @param non-empty-string  $name  Parameter name.
     * @param mixed             $value Parameter value.
     * @param DbalParameterType $type  Optional parameter type.
     *
     * @throws LogicException If parameter with given name already exists.
     */
    public function add(string $name, mixed $value, ArrayParameterType|ParameterType|string|null $type = null): self
    {
        if (\array_key_exists($name, $this->parameters)) {
            throw new LogicException(\sprintf(
                'Cannot add parameter "%s" to parameters bag. Parameter with the same name already exists.',
                $name,
            ));
        }

        $this->parameters[$name] = [$type, $value];

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given name already exists it will be overwritten.
     *
     * @param non-empty-string  $name  Parameter name.
     * @param mixed             $value Parameter value.
     * @param DbalParameterType $type  Optional parameter type.
     */
    public function set(string $name, mixed $value, ArrayParameterType|ParameterType|string|null $type = null): self
    {
        $this->parameters[$name] = [$type, $value];

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
     * @param ParametersInterface<non-empty-string, DbalParameterType> $parameters Parameters bag to merge from.
     * @param bool                                                     $overwrite  If set to true existing parameters will be overwritten.
     */
    public function merge(ParametersInterface $parameters, bool $overwrite = true): self
    {
        foreach ($parameters as [$name, $type, $value]) {
            if ($overwrite) {
                $this->set($name, $value, $type);
                continue;
            }

            $this->add($name, $value, $type);
        }

        return $this;
    }

    /**
     * Set ASCII string parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \Stringable|string|null $value Parameter value.
     */
    public function asciiString(string $name, \Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->set($name, $value, Types::ASCII_STRING);
    }

    /**
     * Set big integer parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param int|null         $value Parameter value.
     */
    public function bigint(string $name, ?int $value): self
    {
        return $this->set($name, $value, Types::BIGINT);
    }

    /**
     * Set binary parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param mixed            $value Parameter value.
     */
    public function binary(string $name, mixed $value): self
    {
        return $this->set($name, $value, Types::BINARY);
    }

    /**
     * Set blob parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param mixed            $value Parameter value.
     */
    public function blob(string $name, mixed $value): self
    {
        return $this->set($name, $value, Types::BLOB);
    }

    /**
     * Set boolean parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param bool|null        $value Parameter value.
     */
    public function boolean(string $name, ?bool $value): self
    {
        return $this->set($name, $value, Types::BOOLEAN);
    }

    /**
     * Set date parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function date(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, $value, Types::DATE_MUTABLE);
    }

    /**
     * Set date immutable parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateImmutable(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, to_date_time_immutable($value), Types::DATE_IMMUTABLE);
    }

    /**
     * Set date interval parameter to the bag.
     *
     * @param non-empty-string   $name  Parameter name.
     * @param \DateInterval|null $value Parameter value.
     */
    public function dateInterval(string $name, ?\DateInterval $value): self
    {
        return $this->set($name, $value, Types::DATEINTERVAL);
    }

    /**
     * Set datetime parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTime(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, $value, Types::DATETIME_MUTABLE);
    }

    /**
     * Set datetime immutable parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeImmutable(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, to_date_time_immutable($value), Types::DATETIME_IMMUTABLE);
    }

    /**
     * Set datetime with timezone parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeTz(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, $value, Types::DATETIMETZ_MUTABLE);
    }

    /**
     * Set datetime with timezone immutable parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function dateTimeTzImmutable(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, to_date_time_immutable($value), Types::DATETIMETZ_IMMUTABLE);
    }

    /**
     * Set decimal parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param float|null       $value Parameter value.
     */
    public function decimal(string $name, ?float $value): self
    {
        return $this->set($name, $value, Types::DECIMAL);
    }

    /**
     * Set float parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param float|null       $value Parameter value.
     */
    public function float(string $name, ?float $value): self
    {
        return $this->set($name, $value, Types::FLOAT);
    }

    /**
     * Set GUID parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \Stringable|string|null $value Parameter value.
     */
    public function guid(string $name, \Stringable|string|null $value): self
    {
        return $this->set($name, $value, Types::GUID);
    }

    /**
     * Set integer parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param int|null         $value Parameter value.
     *
     * @return self
     */
    public function integer(string $name, ?int $value): self
    {
        return $this->set($name, $value, Types::INTEGER);
    }

    /**
     * Set JSON parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param mixed            $value Parameter value.
     */
    public function json(string $name, mixed $value): self
    {
        return $this->set($name, $value, Types::JSON);
    }

    /**
     * Set simple array parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param scalar[]|null    $value Parameter value.
     */
    public function simpleArray(string $name, ?array $value): self
    {
        return $this->set($name, $value, Types::SIMPLE_ARRAY);
    }

    /**
     * Set small integer parameter to the bag.
     *
     * @param non-empty-string $name  Parameter name.
     * @param int|null         $value Parameter value.
     */
    public function smallInt(string $name, ?int $value): self
    {
        return $this->set($name, $value, Types::SMALLINT);
    }

    /**
     * Set string parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \Stringable|string|null $value Parameter value.
     */
    public function string(string $name, \Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->set($name, $value, Types::STRING);
    }

    /**
     * Set text parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \Stringable|string|null $value Parameter value.
     */
    public function text(string $name, \Stringable|string|null $value): self
    {
        $value = $value instanceof \Stringable ? (string)$value : $value;

        return $this->set($name, $value, Types::TEXT);
    }

    /**
     * Set time parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function time(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, $value, Types::TIME_MUTABLE);
    }

    /**
     * Set time immutable parameter to the bag.
     *
     * @param non-empty-string        $name  Parameter name.
     * @param \DateTimeInterface|null $value Parameter value.
     */
    public function timeImmutable(string $name, ?\DateTimeInterface $value): self
    {
        return $this->set($name, to_date_time_immutable($value), Types::TIME_IMMUTABLE);
    }

    /**
     * Set parameter value as enum.
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
     * $parameters->set($name, $value->value, Types::STRING);
     * $parameters->set($name, $value->value, Types::INTEGER);
     * // Non-backed enums
     * $parameters->set($name, $value->name, Types::STRING);
     * ```
     *
     * @param non-empty-string $name  Parameter name.
     * @param \UnitEnum|null   $value Parameter value.
     *
     * @return self
     */
    public function enum(string $name, ?\UnitEnum $value): self
    {
        $extracted = enum_value($value);

        match (true) {
            null === $extracted => $this->set($name, null),
            \is_string($extracted) => $this->string($name, $extracted),
            \is_int($extracted) => $this->integer($name, $extracted),
        };

        return $this;
    }

    /**
     * Set parameter value as array of integers.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param non-empty-string   $name  Parameter name.
     * @param iterable<int>|null $value Parameter value.
     *
     * @return self
     */
    public function integerArray(string $name, ?iterable $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->set($name, null);
        }

        return $this->set($name, \array_values($value), ArrayParameterType::INTEGER);
    }

    /**
     * Set parameter value as array of strings.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param non-empty-string              $name  Parameter name.
     * @param ?iterable<\Stringable|string> $value Parameter value.
     *
     * @return self
     */
    public function stringArray(string $name, ?iterable $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->set($name, null);
        }

        return $this->set(
            $name,
            \array_map('\strval', \array_values($value)),
            ArrayParameterType::STRING
        );
    }

    /**
     * Set parameter value as array of ascii strings.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param non-empty-string              $name  Parameter name.
     * @param ?iterable<\Stringable|string> $value Parameter value.
     *
     * @return self
     */
    public function asciiArray(string $name, ?iterable $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->set($name, null);
        }

        return $this->set(
            $name,
            \array_map('\strval', \array_values($value)),
            ArrayParameterType::ASCII
        );
    }

    /**
     * Set parameter value as array of binary values.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param non-empty-string $name  Parameter name.
     * @param ?iterable<mixed> $value Parameter value.
     *
     * @return self
     */
    public function binaryArray(string $name, ?iterable $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->set($name, null);
        }

        return $this->set($name, \array_values($value), ArrayParameterType::BINARY);
    }

    /**
     * Set parameter value as array of enum values.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * You may used mixed types of enums in the array, but it is not recommended. If only
     * integer backed enums are used, the parameter will be set as integer array. Otherwise,
     * the parameter will be set as string array.
     *
     * @param non-empty-string     $name  Parameter name.
     * @param ?iterable<\UnitEnum> $value Parameter value.
     *
     * @return self
     */
    public function enumArray(string $name, ?iterable $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $values    = [];
        $hasString = false;

        foreach ($value as $current) {
            $extracted = enum_value($current);
            $hasString = $hasString || \is_string($extracted);
            $values[]  = $extracted;
        }

        if (0 === \count($values)) {
            return $this->set($name, null);
        }

        // @phpstan-ignore-next-line
        return $hasString ? $this->stringArray($name, $values) : $this->integerArray($name, $values);
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
