<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Parameters;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\IntegerBackedEnum;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\NonBackedEnum;
use RunOpenCode\Component\Query\Tests\Fixtures\Enums\StringBackedEnum;

final class NamedTest extends TestCase
{
    #[Test]
    public function add(): void
    {
        $bag = new Named();

        $bag->add('foo1', 'v1', 't1');
        $bag->add('foo2', 'v2', 't2');

        $this->assertSame([
            ['foo1', 't1', 'v1'],
            ['foo2', 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function add_throws_exception_if_parameter_already_defined(): void
    {
        $this->expectException(LogicException::class);

        $bag = new Named();

        $bag->add('foo1', 'v1', 't1');
        $bag->add('foo1', 'v2', 't2');
    }

    #[Test]
    public function set(): void
    {
        $bag = new Named();

        $bag->add('foo1', 'v1', 't1');
        $bag->set('foo1', 'v2', 't2');

        $this->assertSame([
            ['foo1', 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function remove(): void
    {
        $bag = new Named();

        $bag->add('foo', 'v1', 't1');
        $bag->add('bar', 'v2', 't2');

        $bag->remove('foo');

        $this->assertSame([
            ['bar', 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function merge_with_overwrite(): void
    {
        $first = new Named();

        $first->add('foo1', 'v1', 't1');
        $first->add('foo2', 'v2', 't2');
        $first->add('foo3', 'v3', 't3');

        $second = new Named();

        $second->add('foo1', 'v4', 't4');
        $second->add('foo2', 'v5', 't5');

        $first->merge($second);

        $this->assertSame([
            ['foo1', 't4', 'v4'],
            ['foo2', 't5', 'v5'],
            ['foo3', 't3', 'v3'],
        ], \iterator_to_array($first));
    }

    #[Test]
    public function merge_throws_exception_if_parameter_already_defined(): void
    {
        $this->expectException(LogicException::class);

        $first = new Named();

        $first->add('foo1', 'v1', 't1');

        $second = new Named();

        $second->add('foo1', 'v2', 't2');

        $first->merge($second, false);
    }

    #[Test]
    public function iterates(): void
    {
        $bag = new Named();

        $bag->add('foo1', 'v1', 't1');
        $bag->add('foo2', 'v2', 't2');

        $this->assertSame([
            ['foo1', 't1', 'v1'],
            ['foo2', 't2', 'v2'],
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function counts(): void
    {
        $bag = new Named();

        $this->assertCount(0, $bag);

        $bag->add('foo1', 'v1', 't1');

        $this->assertCount(1, $bag);
    }

    #[Test]
    public function types(): void
    {
        $bag           = new Named();
        $date          = new \DateTime();
        $dateImmutable = new \DateTimeImmutable();
        $dateInterval  = new \DateInterval('P1D');

        $bag->asciiString('ascii_string', 'foo');
        $bag->bigint('bigint', 42);
        $bag->binary('binary', 42);
        $bag->blob('blob', 42);
        $bag->boolean('boolean', true);
        $bag->date('date', $date);
        $bag->dateImmutable('dateImmutable', $dateImmutable);
        $bag->dateInterval('dateInterval', $dateInterval);
        $bag->dateTime('dateTime', $date);
        $bag->dateTimeImmutable('dateTimeImmutable', $dateImmutable);
        $bag->dateTimeTz('dateTimeTz', $date);
        $bag->dateTimeTzImmutable('dateTimeTzImmutable', $dateImmutable);
        $bag->decimal('decimal', 42.42);
        $bag->float('float', 42.42);
        $bag->guid('guid', '518612f2-c13d-11f0-a4ad-32f700331fbf');
        $bag->integer('integer', 42);
        $bag->json('json', ['foo' => 'bar']);
        $bag->simpleArray('simpleArray', ['foo', 'bar', 'baz']);
        $bag->smallInt('smallInt', 42);
        $bag->string('string', 'foo');
        $bag->text('text', 'foo');
        $bag->time('time', $date);
        $bag->timeImmutable('timeImmutable', $dateImmutable);
        $bag->enum('enum', StringBackedEnum::Bar);
        $bag->integerArray('integerArray', [1, 2, 3, 4]);
        $bag->stringArray('stringArray', ['foo', 'bar', 'baz']);
        $bag->asciiArray('asciiArray', ['foo', 'bar', 'baz']);
        $bag->binaryArray('binaryArray', [1, 2, 3, 4]);
        $bag->enumArray('enumArray', [StringBackedEnum::Bar, StringBackedEnum::Baz]);

        $this->assertSame([
            ['ascii_string', Types::ASCII_STRING, 'foo'],
            ['bigint', Types::BIGINT, 42],
            ['binary', Types::BINARY, 42],
            ['blob', Types::BLOB, 42],
            ['boolean', Types::BOOLEAN, true],
            ['date', Types::DATE_MUTABLE, $date],
            ['dateImmutable', Types::DATE_IMMUTABLE, $dateImmutable],
            ['dateInterval', Types::DATEINTERVAL, $dateInterval],
            ['dateTime', Types::DATETIME_MUTABLE, $date],
            ['dateTimeImmutable', Types::DATETIME_IMMUTABLE, $dateImmutable],
            ['dateTimeTz', Types::DATETIMETZ_MUTABLE, $date],
            ['dateTimeTzImmutable', Types::DATETIMETZ_IMMUTABLE, $dateImmutable],
            ['decimal', Types::DECIMAL, 42.42],
            ['float', Types::FLOAT, 42.42],
            ['guid', Types::GUID, '518612f2-c13d-11f0-a4ad-32f700331fbf'],
            ['integer', Types::INTEGER, 42],
            ['json', Types::JSON, ['foo' => 'bar']],
            ['simpleArray', Types::SIMPLE_ARRAY, ['foo', 'bar', 'baz']],
            ['smallInt', Types::SMALLINT, 42],
            ['string', Types::STRING, 'foo'],
            ['text', Types::TEXT, 'foo'],
            ['time', Types::TIME_MUTABLE, $date],
            ['timeImmutable', Types::TIME_IMMUTABLE, $dateImmutable],
            ['enum', Types::STRING, StringBackedEnum::Bar->value],
            ['integerArray', ArrayParameterType::INTEGER, [1, 2, 3, 4]],
            ['stringArray', ArrayParameterType::STRING, ['foo', 'bar', 'baz']],
            ['asciiArray', ArrayParameterType::ASCII, ['foo', 'bar', 'baz']],
            ['binaryArray', ArrayParameterType::BINARY, [1, 2, 3, 4]],
            ['enumArray', ArrayParameterType::STRING, ['bar', 'baz']],
        ], \iterator_to_array($bag));
    }

    #[Test]
    #[TestWith([StringBackedEnum::Foo, Types::STRING, 'foo'], 'String backed enum.')]
    #[TestWith([IntegerBackedEnum::Foo, Types::INTEGER, 1], 'Integer backed enum.')]
    #[TestWith([NonBackedEnum::Foo, Types::STRING, 'Foo'], 'Non-backed enum.')]
    #[TestWith([null, null, null], 'Null value.')]
    public function enum(?\UnitEnum $source, ?string $type, string|int|null $value): void
    {
        $bag = new Named();

        $bag->enum('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
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
        $bag = new Named();

        $bag->integerArray('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
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
        $bag = new Named();

        $bag->stringArray('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
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
        $bag = new Named();

        $bag->asciiArray('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
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
        $bag = new Named();

        $bag->binaryArray('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
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
        $bag = new Named();

        $bag->enumArray('foo', $source);

        $this->assertSame($type, $bag->types['foo']);
        $this->assertSame($value, $bag->values['foo']);
    }
}
