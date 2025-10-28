<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use RunOpenCode\Component\Query\Contract\Executor\ParametersInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Parameters\Named as NamedParametersBag;
use RunOpenCode\Component\Query\Parameters\Parameter;

/**
 * Named parameters bag for Doctrine queries.
 *
 * @implements ParametersInterface<non-empty-string>
 */
final class Named implements ParametersInterface
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

    private readonly NamedParametersBag $bag;

    /**
     * Creates new instance of named parameters bag for Doctrine queries.
     *
     * @param array<non-empty-string, Parameter> $parameters
     */
    public function __construct(
        array $parameters = [],
    ) {
        $this->bag = new NamedParametersBag($parameters);
    }

    /**
     * Add new parameter to the bag.
     *
     * @param non-empty-string                         $name  Parameter name.
     * @param mixed                                    $value Parameter value.
     * @param ArrayParameterType|non-empty-string|null $type  Optional parameter type.
     *
     * @throws LogicException If parameter with given name already exists.
     */
    public function add(string $name, mixed $value, ArrayParameterType|string|null $type = null): self
    {
        $this->bag->add($name, $value, $type);

        return $this;
    }

    /**
     * Set parameter to the bag.
     *
     * If parameter with given name already exists it will be overwritten.
     *
     * @param non-empty-string                         $name  Parameter name.
     * @param mixed                                    $value Parameter value.
     * @param ArrayParameterType|non-empty-string|null $type  Optional parameter type.
     */
    public function set(string $name, mixed $value, ArrayParameterType|string|null $type = null): self
    {
        $this->bag->set($name, $value, $type);

        return $this;
    }

    /**
     * Removes parameter from the bag.
     *
     * If parameter with given name does not exist, no action is performed.
     *
     * @param non-empty-string $name Parameter name.
     */
    public function remove(string $name): self
    {
        $this->bag->remove($name);

        return $this;
    }

    /**
     * Merges another parameters bag into this one.
     *
     * @param ParametersInterface<non-empty-string> $parameters Parameters bag to merge from.
     * @param bool                                  $overwrite  If set to true existing parameters will be overwritten.
     */
    public function merge(ParametersInterface $parameters, bool $overwrite = true): self
    {
        $this->bag->merge($parameters, $overwrite);

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
        return $this->set($name, $value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATE_IMMUTABLE);
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
        return $this->set($name, $value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATETIME_IMMUTABLE);
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
        return $this->set($name, $value ? \DateTimeImmutable::createFromInterface($value) : null, Types::DATETIMETZ_IMMUTABLE);
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
        return $this->set($name, $value ? \DateTimeImmutable::createFromInterface($value) : null, Types::TIME_IMMUTABLE);
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
     * @param ?\UnitEnum       $value Parameter value.
     *
     * @return self
     */
    public function enum(string $name, ?\UnitEnum $value): self
    {
        if (null === $value) {
            return $this->set($name, null);
        }

        $reflection = new \ReflectionEnum($value::class);

        if (!$reflection->isBacked()) {
            return $this->string($name, $value->name);
        }

        /** @var \BackedEnum $value */
        return \is_string($value->value) ? $this->string($name, $value->value) : $this->integer($name, $value->value);
    }

    /**
     * Set parameter value as array of integers.
     *
     * If you provide null value, or empty iterable, the parameter will be set to null.
     *
     * @param non-empty-string $name  Parameter name.
     * @param ?iterable<int>   $value Parameter value.
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

        $value = \is_array($value) ? $value : \iterator_to_array($value);

        if (0 === \count($value)) {
            return $this->set($name, null);
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
        return $hasString ? $this->stringArray($name, $values) : $this->integerArray($name, $values);
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