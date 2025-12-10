<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\DbalDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Converted;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Convert;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Tests\PHPUnit\DbalTools;

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
     * @return iterable<string, array{string, Convert, mixed, string}>
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

    #[Test]
    #[DataProvider('get_data_for_vector')]
    public function vector(string $query, Convert $configuration, mixed $expected, string $assert): void
    {
        $result = $this->executeQuery($query, $configuration);
        $vector = $result->vector();

        $this->assertCount(\count($expected), $vector);

        foreach ($expected as $index => $value) {
            match ($assert) {
                'assertBoolean' => $this->assertBoolean($expected[$index], $vector[$index]),
                'assertFloat' => $this->assertFloat($expected[$index], $vector[$index]),
                'assertImmutableDateTime' => $this->assertImmutableDateTime($expected[$index], $vector[$index]),
                default => $this->fail(\sprintf('Provided assert function "%s" is unsupported.', $assert)),
            };
        }
    }

    /**
     * @return iterable<string, array{string, mixed}>
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

    private function executeQuery(string $query, Convert $configuration): Converted
    {
        return new Converted(
            new Result(new DbalDataset('default', $this->connection->executeQuery($query))),
            $configuration,
            $this->platform
        );
    }

    private function assertBoolean(bool $expected, mixed $actual): void
    {
        $this->assertIsBool($actual);
        $this->assertSame($expected, $actual);
    }

    private function assertFloat(float $expected, mixed $actual): void
    {
        $this->assertIsFloat($actual);
        $this->assertEqualsWithDelta($expected, $actual, 0.1);
    }

    private function assertImmutableDateTime(\DateTimeInterface $expected, mixed $actual): void
    {
        $this->assertInstanceOf(\DateTimeImmutable::class, $actual);
        $this->assertSame(
            $expected->format(\DateTimeInterface::ATOM),
            $actual->format(\DateTimeInterface::ATOM)
        );
    }
}
