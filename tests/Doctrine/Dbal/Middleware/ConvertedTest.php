<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\DbalDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Converted;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Convert;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Tests\PHPUnit\DbalTools;

/**
 * @phpstan-type InternalAssert = 'assertBoolean'|'assertFloat'|'assertImmutableDateTime'
 */
final class ConvertedTest extends TestCase
{
    use DbalTools;

    private Connection $connection;

    private AbstractPlatform $platform;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createSqlLiteConnection('conversion');
        $this->platform   = $this->connection->getDatabasePlatform();
    }

    #[Test]
    #[DataProvider('get_data_for_scalar')]
    public function scalar(string $query, Convert $configuration, mixed $expected, string $assert): void
    {
        $result = $this->executeQuery($query, $configuration);

        match ($assert) {
            'assertBoolean' => $this->assertBoolean($expected, $result->scalar()),
            'assertFloat' => $this->assertFloat($expected, $result->scalar()),
            'assertImmutableDateTime' => $this->assertImmutableDateTime($expected, $result->scalar()),
            default => $this->fail(\sprintf('Provided assert function "%s" is unsupported.', $assert)),
        };
    }

    /**
     * @return iterable<string, array{string, Convert, mixed, InternalAssert}>
     */
    public static function get_data_for_scalar(): iterable
    {
        yield 'One column, one row, boolean result.' => [
            'SELECT boolean_value FROM conversion WHERE id = 1',
            new Convert()->boolean('boolean_value'),
            true,
            'assertBoolean',
        ];

        yield 'Multiple columns, one row, int result.' => [
            'SELECT boolean_value, id FROM conversion WHERE id = 1',
            new Convert()->boolean('boolean_value'),
            true,
            'assertBoolean',
        ];

        yield 'One column, one row, float result.' => [
            'SELECT float_value FROM conversion WHERE id = 3',
            new Convert()->float('float_value'),
            1.3,
            'assertFloat',
        ];

        yield 'Multiple columns, one row, date result.' => [
            'SELECT date_value, id FROM conversion WHERE id = 2',
            new Convert()->dateImmutable('date_value'),
            new \DateTimeImmutable('2005-10-12'),
            'assertImmutableDateTime',
        ];
    }

    #[Test]
    public function scalar_returns_default_when_no_results(): void
    {
        $result = $this->executeQuery('SELECT id FROM conversion WHERE id = -1', new Convert()->integer('id'));

        $this->assertSame(42, $result->scalar(42));
    }

    #[Test]
    public function scalar_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $result = $this->executeQuery('SELECT id FROM conversion WHERE id = -1', new Convert()->integer('id'));

        $result->scalar();
    }

    #[Test]
    public function scalar_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $result = $this->executeQuery('SELECT id FROM conversion', new Convert()->integer('id'));

        $result->scalar();
    }

    /**
     * @param mixed[]        $expected
     * @param InternalAssert $assert
     */
    #[Test]
    #[DataProvider('get_data_for_vector')]
    public function vector(string $query, Convert $configuration, array $expected, string $assert): void
    {
        $result = $this->executeQuery($query, $configuration);
        /** @var mixed[] $vector */
        $vector = $result->vector();

        $this->assertCount(\count($expected), $vector);

        foreach ($expected as $index => $value) {
            match ($assert) {
                'assertBoolean' => $this->assertBoolean($expected[$index], $vector[$index]),
                'assertFloat' => $this->assertFloat($expected[$index], $vector[$index]),
                'assertImmutableDateTime' => $this->assertImmutableDateTime($expected[$index], $vector[$index]), // @phpstan-ignore-line match.alwaysTrue
                default => $this->fail(\sprintf('Provided assert function "%s" is unsupported.', $assert)),
            };
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, Convert, mixed[], InternalAssert}>
     */
    public static function get_data_for_vector(): iterable
    {
        yield 'One column, one row, boolean result.' => [
            'SELECT boolean_value FROM conversion WHERE id = 1',
            new Convert()->boolean('boolean_value'),
            [true],
            'assertBoolean',
        ];

        yield 'Multiple columns, multiple rows, boolean result.' => [
            'SELECT boolean_value, id FROM conversion ORDER BY id',
            new Convert()->boolean('boolean_value'),
            [true, false, true],
            'assertBoolean',
        ];

        yield 'One column, multiple rows, float result.' => [
            'SELECT float_value FROM conversion ORDER BY id',
            new Convert()->float('float_value'),
            [1.1, 1.2, 1.3],
            'assertFloat',
        ];

        yield 'Multiple columns, multiple row, date result.' => [
            'SELECT date_value, id FROM conversion ORDER BY id',
            new Convert()->dateImmutable('date_value'),
            [new \DateTimeImmutable('2005-10-11'), new \DateTimeImmutable('2005-10-12'), new \DateTimeImmutable('2005-10-13')],
            'assertImmutableDateTime',
        ];
    }

    #[Test]
    public function vector_returns_default_when_no_results(): void
    {
        $this->assertSame(['foo', 'bar'], $this->executeQuery('SELECT id FROM conversion WHERE id = -1', new Convert()->integer('id'))->vector(['foo', 'bar']));
    }

    #[Test]
    public function record(): void
    {
        /**
         * @var array<non-empty-string, mixed> $record
         */
        $record = $this->executeQuery(
            'SELECT * FROM conversion WHERE id = 1',
            new Convert()
                ->integer('id')
                ->string('text_value')
                ->float('float_value')
                ->dateImmutable('date_value')
                ->boolean('boolean_value')
        )->record();

        $this->assertCount(5, $record);

        $this->assertSame(1, $record['id']);
        $this->assertSame('Text value 1', $record['text_value']);
        $this->assertFloat(1.1, $record['float_value']);
        $this->assertImmutableDateTime(new \DateTimeImmutable('2005-10-11'), $record['date_value']);
        $this->assertBoolean(true, $record['boolean_value']);
    }

    #[Test]
    public function record_returns_default_when_no_results(): void
    {
        $this->assertSame(['foo', 'bar'], $this->executeQuery(
            'SELECT * FROM conversion WHERE id = -1',
            new Convert()
                ->integer('id')
        )->record(['foo', 'bar']));
    }

    #[Test]
    public function record_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $this->executeQuery('SELECT * FROM conversion WHERE id = -1', new Convert()->integer('id'))->record();
    }

    #[Test]
    public function record_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $this->executeQuery('SELECT * FROM conversion', new Convert()->integer('id'))->record();
    }

    #[Test]
    #[TestWith(['scalar'], 'Method scalar()')]
    #[TestWith(['vector'], 'Method vector()')]
    #[TestWith(['record'], 'Method record()')]
    public function methods_with_variadic_defaults_throw_exception_on_multiple_default_values(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);

        $result = new Converted(new Result(new ArrayDataset('default', [])), new Convert(), $this->platform);
        $result->{$method}('foo', 'bar');
    }

    #[Test]
    public function iterates(): void
    {
        $result  = $this->executeQuery(
            'SELECT id, text_value, boolean_value FROM conversion WHERE id IN (1, 2) ORDER BY id',
            new Convert()
                ->integer('id')
                ->string('text_value')
                ->boolean('boolean_value')
        );
        $fetched = [];

        foreach ($result as $row) {
            $fetched[] = $row;
        }

        $this->assertSame([
            ['id' => 1, 'text_value' => 'Text value 1', 'boolean_value' => true],
            ['id' => 2, 'text_value' => 'Text value 2', 'boolean_value' => false],
        ], $fetched);
    }

    private function executeQuery(string $query, Convert $configuration): Converted
    {
        return new Converted(
            new Result(new DbalDataset('default', $this->connection->executeQuery($query))),
            $configuration,
            $this->platform
        );
    }

    private function assertBoolean(mixed $expected, mixed $actual): void
    {
        \assert(\is_bool($expected), new \InvalidArgumentException(\sprintf(
            'Expected boolean, got "%s".',
            \get_debug_type($expected)
        )));

        $this->assertIsBool($actual);
        $this->assertSame($expected, $actual);
    }

    private function assertFloat(mixed $expected, mixed $actual): void
    {
        \assert(\is_float($expected), new \InvalidArgumentException(\sprintf(
            'Expected float, got "%s".',
            \get_debug_type($expected)
        )));

        $this->assertIsFloat($actual);
        $this->assertEqualsWithDelta($expected, $actual, 0.1);
    }

    private function assertImmutableDateTime(mixed $expected, mixed $actual): void
    {
        \assert($expected instanceof \DateTimeImmutable, new \InvalidArgumentException(\sprintf(
            'Expected instance of "%s", got "%s".',
            \DateTimeImmutable::class,
            \get_debug_type($expected)
        )));

        $this->assertInstanceOf(\DateTimeImmutable::class, $actual);
        $this->assertSame(
            $expected->format(\DateTimeInterface::ATOM),
            $actual->format(\DateTimeInterface::ATOM)
        );
    }
}
