<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal\Middleware;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Types;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * @phpstan-type DbalColumnType = non-empty-string
 * @phpstan-type DbalColumCustomConverter = callable(mixed, AbstractPlatform $platform): mixed
 *
 * @implements \IteratorAggregate<non-empty-string, DbalColumnType>
 */
final class Convert implements \IteratorAggregate, \Countable
{
    /**
     * @var array<non-empty-string, DbalColumnType|DbalColumCustomConverter>
     */
    private array $columns = [];

    public function __construct()
    {
        // noop.
    }

    /**
     * Check if column has defined conversion.
     *
     * @param non-empty-string $name Column name.
     */
    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->columns);
    }

    /**
     * Get defined converter.
     *
     * @param non-empty-string $name Column name.
     */
    public function get(string $name): string|callable
    {
        if (!$this->has($name)) {
            throw new RuntimeException(\sprintf(
                'Conversion for column "%s" is not defined.',
                $name
            ));
        }

        return $this->columns[$name];
    }

    /**
     * Add column type.
     *
     * @param non-empty-string                        $name Column name.
     * @param DbalColumnType|DbalColumCustomConverter $type Column type.
     *
     * @throws LogicException If column with given name already exists.
     */
    public function add(string $name, callable|string $type): self
    {
        if (\array_key_exists($name, $this->columns)) {
            throw new LogicException(\sprintf(
                'Cannot add column "%s", column with the same name already exists.',
                $name,
            ));
        }

        $this->columns[$name] = $type;

        return $this;
    }

    /**
     * Set column type.
     *
     * If column with given name already exists it will be overwritten.
     *
     * @param non-empty-string                        $name Column name.
     * @param DbalColumnType|DbalColumCustomConverter $type Column type.
     */
    public function set(string $name, callable|string $type): self
    {
        $this->columns[$name] = $type;

        return $this;
    }

    /**
     * Remove column.
     *
     * If column with given name does not exist, no action is performed.
     *
     * @param non-empty-string $name Column name.
     */
    public function remove(string $name): self
    {
        unset($this->columns[$name]);

        return $this;
    }

    /**
     * Set ASCII string column.
     *
     * @param non-empty-string $name Column name.
     */
    public function asciiString(string $name): self
    {
        return $this->set($name, Types::ASCII_STRING);
    }

    /**
     * Set big integer column.
     *
     * @param non-empty-string $name Column name.
     */
    public function bigint(string $name): self
    {
        return $this->set($name, Types::BIGINT);
    }

    /**
     * Set binary column.
     *
     * @param non-empty-string $name Column name.
     */
    public function binary(string $name): self
    {
        return $this->set($name, Types::BINARY);
    }

    /**
     * Set blob column.
     *
     * @param non-empty-string $name Column name.
     */
    public function blob(string $name): self
    {
        return $this->set($name, Types::BLOB);
    }

    /**
     * Set boolean column.
     *
     * @param non-empty-string $name Column name.
     */
    public function boolean(string $name): self
    {
        return $this->set($name, Types::BOOLEAN);
    }

    /**
     * Set date column.
     *
     * @param non-empty-string $name Column name.
     */
    public function date(string $name): self
    {
        return $this->set($name, Types::DATE_MUTABLE);
    }

    /**
     * Set date immutable column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateImmutable(string $name): self
    {
        return $this->set($name, Types::DATE_IMMUTABLE);
    }

    /**
     * Set date interval column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateInterval(string $name): self
    {
        return $this->set($name, Types::DATEINTERVAL);
    }

    /**
     * Set datetime column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateTime(string $name): self
    {
        return $this->set($name, Types::DATETIME_MUTABLE);
    }

    /**
     * Set datetime immutable column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateTimeImmutable(string $name): self
    {
        return $this->set($name, Types::DATETIME_IMMUTABLE);
    }

    /**
     * Set datetime with timezone column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateTimeTz(string $name): self
    {
        return $this->set($name, Types::DATETIMETZ_MUTABLE);
    }

    /**
     * Set datetime with timezone immutable column.
     *
     * @param non-empty-string $name Column name.
     */
    public function dateTimeTzImmutable(string $name): self
    {
        return $this->set($name, Types::DATETIMETZ_IMMUTABLE);
    }

    /**
     * Set decimal column.
     *
     * @param non-empty-string $name Column name.
     */
    public function decimal(string $name): self
    {
        return $this->set($name, Types::DECIMAL);
    }

    /**
     * Set float column.
     *
     * @param non-empty-string $name Column name.
     */
    public function float(string $name): self
    {
        return $this->set($name, Types::FLOAT);
    }

    /**
     * Set GUID column.
     *
     * @param non-empty-string $name Column name.
     */
    public function guid(string $name): self
    {
        return $this->set($name, Types::GUID);
    }

    /**
     * Set integer column.
     *
     * @param non-empty-string $name Column name.
     */
    public function integer(string $name): self
    {
        return $this->set($name, Types::INTEGER);
    }

    /**
     * Set JSON column.
     *
     * @param non-empty-string $name Column name.
     */
    public function json(string $name): self
    {
        return $this->set($name, Types::JSON);
    }

    /**
     * Set simple array column.
     *
     * @param non-empty-string $name Column name.
     */
    public function simpleArray(string $name): self
    {
        return $this->set($name, Types::SIMPLE_ARRAY);
    }

    /**
     * Set small integer column.
     *
     * @param non-empty-string $name Column name.
     */
    public function smallInt(string $name): self
    {
        return $this->set($name, Types::SMALLINT);
    }

    /**
     * Set string column.
     *
     * @param non-empty-string $name Column name.
     */
    public function string(string $name): self
    {
        return $this->set($name, Types::STRING);
    }

    /**
     * Set text column.
     *
     * @param non-empty-string $name Column name.
     */
    public function text(string $name): self
    {
        return $this->set($name, Types::TEXT);
    }

    /**
     * Set time column.
     *
     * @param non-empty-string $name Column name.
     */
    public function time(string $name): self
    {
        return $this->set($name, Types::TIME_MUTABLE);
    }

    /**
     * Set time immutable column.
     *
     * @param non-empty-string $name Column name.
     */
    public function timeImmutable(string $name): self
    {
        return $this->set($name, Types::TIME_IMMUTABLE);
    }

    /**
     * Set enum column.
     *
     * @param non-empty-string        $name    Column name.
     * @param class-string<\UnitEnum> ...$enum Enum type. If multiple provided, first matching will be used.
     */
    public function enum(string $name, string ...$enum): self
    {
        if (0 === \count($enum)) {
            throw new LogicException('At least one enum must be provided.');
        }

        $this->columns[$name] = static function(string|int|null $value) use ($enum): ?\UnitEnum {
            if (null === $value) {
                return null;
            }

            /** @var class-string<\UnitEnum> $current */
            foreach ($enum as $current) {
                $value = $current::tryFrom($value);

                if (null !== $value) {
                    return $value;
                }
            }

            throw new RuntimeException(\sprintf(
                'Non of the given enums "%s" are not compatible with serialized value "%s".',
                \implode(', ', $enum),
                $value,
            ));
        };

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->columns;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->columns);
    }
}
