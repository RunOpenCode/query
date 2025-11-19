<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Doctrine\Parameters\Positional;
use RunOpenCode\Component\Query\Exception\OutOfBoundsException;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\IntegerBackedEnum;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\NonBackedEnum;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\StringBackedEnum;

final class PositionalTest extends TestCase
{
    #[Test]
    public function add(): void
    {
        $bag = new Positional();

        $bag->add('v1', 't1');
        $bag->add('v2', 't2');

        $this->assertSame([
            [0, 't1', 'v1'],
            [1, 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function set(): void
    {
        $bag = new Positional();

        $bag->add('v1', 't1');
        $bag->set(0, 'v2', 't2');

        $this->assertSame([
            [0, 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function set_throws_out_of_bounds_exception(): void
    {
        $this->expectException(OutOfBoundsException::class);

        $bag = new Positional();

        $bag->set(1, 'v1', 't1');
    }

    #[Test]
    public function remove(): void
    {
        $bag = new Positional();

        $bag->add('v1', 't1');
        $bag->add('v2', 't2');

        $bag->remove(0);

        $this->assertSame([
            [0, 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function merge_positional(): void
    {
        $first  = new Positional();
        $second = new Positional();

        $first->add('v1', 't1');
        $second->add('v2', 't2');

        $first->merge($second);

        $this->assertSame([
            [0, 't1', 'v1'],
            [1, 't2', 'v2'],
        ], \iterator_to_array($first));
    }

    #[Test]
    public function merge_named(): void
    {
        $first  = new Positional();
        $second = new Named();

        $first->add('v1', 't1');
        $second->add('foo', 'v2', 't2');

        $first->merge($second);

        $this->assertSame([
            [0, 't1', 'v1'],
            [1, 't2', 'v2'],
        ], \iterator_to_array($first));
    }


    #[Test]
    public function iterates(): void
    {
        $bag = new Positional();

        $bag->add('v1', 't1');
        $bag->add('v2', 't2');

        $this->assertSame([
            [0, 't1', 'v1'],
            [1, 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function counts(): void
    {
        $bag = new Positional();

        $this->assertCount(0, $bag);

        $bag->add('v1', 't1');

        $this->assertCount(1, $bag);
    }

    #[Test]
    public function types(): void
    {
        $bag           = new Positional();
        $date          = new \DateTime();
        $dateImmutable = new \DateTimeImmutable();
        $dateInterval  = new \DateInterval('P1D');

        $bag->asciiString('foo');
        $bag->bigint(42);
        $bag->binary(42);
        $bag->blob(42);
        $bag->boolean(true);
        $bag->date($date);
        $bag->dateImmutable($dateImmutable);
        $bag->dateInterval($dateInterval);
        $bag->dateTime($date);
        $bag->dateTimeImmutable($dateImmutable);
        $bag->dateTimeTz($date);
        $bag->dateTimeTzImmutable($dateImmutable);
        $bag->decimal(42.42);
        $bag->float(42.42);
        $bag->guid('518612f2-c13d-11f0-a4ad-32f700331fbf');
        $bag->integer(42);
        $bag->json(['foo' => 'bar']);
        $bag->simpleArray(['foo', 'bar', 'baz']);
        $bag->smallInt(42);
        $bag->string('foo');
        $bag->text('foo');
        $bag->time($date);
        $bag->timeImmutable($dateImmutable);
        $bag->enum(StringBackedEnum::Bar);
        $bag->integerArray([1, 2, 3, 4]);
        $bag->stringArray(['foo', 'bar', 'baz']);
        $bag->asciiArray(['foo', 'bar', 'baz']);
        $bag->binaryArray([1, 2, 3, 4]);
        $bag->enumArray([StringBackedEnum::Bar, StringBackedEnum::Baz]);

        $this->assertSame([
            [0, Types::ASCII_STRING, 'foo'],
            [1, Types::BIGINT, 42],
            [2, Types::BINARY, 42],
            [3, Types::BLOB, 42],
            [4, Types::BOOLEAN, true],
            [5, Types::DATE_MUTABLE, $date],
            [6, Types::DATE_IMMUTABLE, $dateImmutable],
            [7, Types::DATEINTERVAL, $dateInterval],
            [8, Types::DATETIME_MUTABLE, $date],
            [9, Types::DATETIME_IMMUTABLE, $dateImmutable],
            [10, Types::DATETIMETZ_MUTABLE, $date],
            [11, Types::DATETIMETZ_IMMUTABLE, $dateImmutable],
            [12, Types::DECIMAL, 42.42],
            [13, Types::FLOAT, 42.42],
            [14, Types::GUID, '518612f2-c13d-11f0-a4ad-32f700331fbf'],
            [15, Types::INTEGER, 42],
            [16, Types::JSON, ['foo' => 'bar']],
            [17, Types::SIMPLE_ARRAY, ['foo', 'bar', 'baz']],
            [18, Types::SMALLINT, 42],
            [19, Types::STRING, 'foo'],
            [20, Types::TEXT, 'foo'],
            [21, Types::TIME_MUTABLE, $date],
            [22, Types::TIME_IMMUTABLE, $dateImmutable],
            [23, Types::STRING, StringBackedEnum::Bar->value],
            [24, ArrayParameterType::INTEGER, [1, 2, 3, 4]],
            [25, ArrayParameterType::STRING, ['foo', 'bar', 'baz']],
            [26, ArrayParameterType::ASCII, ['foo', 'bar', 'baz']],
            [27, ArrayParameterType::BINARY, [1, 2, 3, 4]],
            [28, ArrayParameterType::STRING, ['bar', 'baz']],
        ], \iterator_to_array($bag));
    }

    #[Test]
    #[TestWith([StringBackedEnum::Foo, Types::STRING, 'foo'], 'String backed enum.')]
    #[TestWith([IntegerBackedEnum::Foo, Types::INTEGER, 1], 'Integer backed enum.')]
    #[TestWith([NonBackedEnum::Foo, Types::STRING, 'Foo'], 'Non-backed enum.')]
    #[TestWith([null, null, null], 'Null value.')]
    public function enum(?\UnitEnum $source, ?string $type, string|int|null $value): void
    {
        $bag = new Positional();

        $bag->enum($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }

    /**
     * @param ?int[] $source
     * @param ?int[] $value
     */
    #[Test]
    #[TestWith([null, null, null], 'Null value.')]
    #[TestWith([[], null, null], 'Empty array value.')]
    #[TestWith([[1, 2, 3], ArrayParameterType::INTEGER, [1, 2, 3]], 'Integer values.')]
    public function integer_array(?array $source, ?ArrayParameterType $type, ?array $value): void
    {
        $bag = new Positional();

        $bag->integerArray($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }

    /**
     * @param ?string[] $source
     * @param ?string[] $value
     */
    #[Test]
    #[TestWith([null, null, null], 'Null value.')]
    #[TestWith([[], null, null], 'Empty array value.')]
    #[TestWith([['foo', 'bar', 'baz'], ArrayParameterType::STRING, ['foo', 'bar', 'baz']], 'String values.')]
    public function string_array(?array $source, ?ArrayParameterType $type, ?array $value): void
    {
        $bag = new Positional();

        $bag->stringArray($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }


    /**
     * @param ?string[] $source
     * @param ?string[] $value
     */
    #[Test]
    #[TestWith([null, null, null], 'Null value.')]
    #[TestWith([[], null, null], 'Empty array value.')]
    #[TestWith([['foo', 'bar', 'baz'], ArrayParameterType::ASCII, ['foo', 'bar', 'baz']], 'String values.')]
    public function ascii_array(?array $source, ?ArrayParameterType $type, ?array $value): void
    {
        $bag = new Positional();

        $bag->asciiArray($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }

    /**
     * @param ?int[] $source
     * @param ?int[] $value
     */
    #[Test]
    #[TestWith([null, null, null], 'Null value.')]
    #[TestWith([[], null, null], 'Empty array value.')]
    #[TestWith([[1, 2, 3], ArrayParameterType::BINARY, [1, 2, 3]], 'Integer values.')]
    public function binary_array(?array $source, ?ArrayParameterType $type, ?array $value): void
    {
        $bag = new Positional();

        $bag->binaryArray($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }

    /**
     * @param ?\UnitEnum[]          $source
     * @param list<int|string>|null $value
     */
    #[Test]
    #[TestWith([null, null, null], 'Null value.')]
    #[TestWith([[], null, null], 'Empty array value.')]
    #[TestWith([[StringBackedEnum::Foo, StringBackedEnum::Bar, StringBackedEnum::Baz], ArrayParameterType::STRING, ['foo', 'bar', 'baz']], 'String backed enum values.')]
    #[TestWith([[IntegerBackedEnum::Foo, IntegerBackedEnum::Bar, IntegerBackedEnum::Baz], ArrayParameterType::INTEGER, [1, 2, 3]], 'Integer backed enum values.')]
    #[TestWith([[NonBackedEnum::Foo, NonBackedEnum::Bar, NonBackedEnum::Baz], ArrayParameterType::STRING, ['Foo', 'Bar', 'Baz']], 'String enum names.')]
    #[TestWith([[StringBackedEnum::Foo, IntegerBackedEnum::Foo, NonBackedEnum::Foo], ArrayParameterType::STRING, ['foo', '1', 'Foo']], 'Mixed enum values and names.')]
    public function enum_array(?array $source, ?ArrayParameterType $type, ?array $value): void
    {
        $bag = new Positional();

        $bag->enumArray($source);

        $this->assertSame($type, $bag->types[0]);
        $this->assertSame($value, $bag->values[0]);
    }
}
