<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PgSQL\Exception\UnknownParameter;
use Doctrine\DBAL\Driver\Result as DbalDriverResult;
use Doctrine\DBAL\Result as DbalResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Tests\PHPUnit\DbalTools;

final class ResultTest extends TestCase
{
    use DbalTools;

    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createSqlLiteConnection();
    }

    #[Test]
    #[DataProvider('get_data_for_get_scalar')]
    public function get_scalar(string $query, mixed $expected): void
    {
        $result = new Result($this->connection->executeQuery($query));

        $this->assertSame($expected, $result->getScalar());
    }

    /**
     * @return iterable<string, array{string, scalar}>
     */
    public static function get_data_for_get_scalar(): iterable
    {
        yield 'One column, one row, int result.' => ['SELECT id FROM test WHERE id = 1', 1];
        yield 'Multiple columns, one row, int result.' => ['SELECT id, title FROM test WHERE id = 1', 1];
        yield 'One column, one row, string result.' => ['SELECT title FROM test WHERE id = 3', 'Title 3'];
    }

    #[Test]
    public function get_scalar_returns_default_when_no_results(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id FROM test WHERE id = -1'));

        $this->assertSame(42, $result->getScalar(42));
    }

    #[Test]
    public function get_scalar_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $result = new Result($this->connection->executeQuery('SELECT id FROM test WHERE id = -1'));

        $result->getScalar();
    }

    #[Test]
    public function get_scalar_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $result = new Result($this->connection->executeQuery('SELECT id FROM test'));

        $result->getScalar();
    }

    #[Test]
    #[DataProvider('get_data_for_get_vector')]
    public function get_vector(string $query, mixed $expected): void
    {
        $result = new Result($this->connection->executeQuery($query));

        $this->assertSame($expected, $result->getVector());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function get_data_for_get_vector(): iterable
    {
        yield 'One column, int result.' => ['SELECT id FROM test ORDER BY id', [1, 2, 3, 4, 5]];
        yield 'Multiple columns, int result.' => ['SELECT id, title FROM test ORDER BY id', [1, 2, 3, 4, 5]];
        yield 'One column, string result.' => ['SELECT title FROM test ORDER BY id', ['Title 1', 'Title 2', 'Title 3', 'Title 4', 'Title 5']];
        yield 'One column, no results.' => ['SELECT title FROM test WHERE id = -1', []];
    }

    #[Test]
    public function get_vector_returns_default_when_no_results(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id FROM test WHERE id = -1'));

        $this->assertSame(['foo', 'bar'], $result->getVector(['foo', 'bar']));
    }

    #[Test]
    public function get_record(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id = 1'));

        $this->assertSame(['id' => 1, 'title' => 'Title 1'], $result->getRecord());
    }

    #[Test]
    public function get_record_returns_default_when_no_results(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT * FROM test WHERE id = -1'));

        $this->assertSame(['foo', 'bar'], $result->getRecord(['foo', 'bar']));
    }

    #[Test]
    public function get_record_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $result = new Result($this->connection->executeQuery('SELECT * FROM test WHERE id = -1'));

        $result->getRecord();
    }

    #[Test]
    public function get_record_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $result = new Result($this->connection->executeQuery('SELECT * FROM test'));

        $result->getRecord();
    }

    #[TestWith(['getScalar'], 'Method getScalar()')]
    #[TestWith(['getVector'], 'Method getVector()')]
    #[TestWith(['getRecord'], 'Method getRecord()')]
    #[Test]
    public function methods_with_variadic_defaults_throw_exception_on_multiple_default_values(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);

        $result = new Result($this->createMock(DbalResult::class));
        $result->{$method}('foo', 'bar');
    }

    #[Test]
    public function column_count(): void
    {
        $this->assertSame(2, new Result($this->connection->executeQuery('SELECT id, title FROM test'))->columnCount());
    }

    #[Test]
    public function fetch_associative(): void
    {
        $result  = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));
        $fetched = [];

        while (false !== ($row = $result->fetchAssociative())) {
            $fetched[] = $row;
        }

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], $fetched);
    }

    #[Test]
    public function fetch_numeric(): void
    {
        $result  = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));
        $fetched = [];

        while (false !== ($row = $result->fetchNumeric())) {
            $fetched[] = $row;
        }

        $this->assertSame([
            [1, 'Title 1'],
            [2, 'Title 2'],
        ], $fetched);
    }

    #[Test]
    public function fetch_one(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id = 1'));

        $this->assertSame(1, $result->fetchOne());
    }

    #[Test]
    public function fetch_all_associative(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], $result->fetchAllAssociative());
    }

    #[Test]
    public function fetch_all_numeric(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));

        $this->assertSame([
            [1, 'Title 1'],
            [2, 'Title 2'],
        ], $result->fetchAllNumeric());
    }

    #[Test]
    public function fetch_first_column(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));

        $this->assertSame([1, 2], $result->fetchFirstColumn());
    }

    #[Test]
    public function row_count(): void
    {
        $result = new Result($this->connection->executeQuery('DELETE FROM test WHERE id IN (1, 2)'));

        $this->assertSame(2, $result->rowCount());
    }

    #[Test]
    public function counts(): void
    {
        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));

        $this->assertCount(2, $result);
    }

    #[Test]
    public function iterates(): void
    {
        $result  = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));
        $fetched = [];

        foreach ($result as $row) {
            $fetched[] = $row;
        }

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], $fetched);
    }

    #[Test]
    #[DataProvider('get_data_for_free')]
    public function free(string $method): void
    {
        $this->expectException(LogicException::class);

        $result = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));

        $result->free();

        // A small trick applied, method will either throw LogicException, or,
        // if method is `getIterator()`, `iterator_to_array` will trigger it
        // and we will catch it as well.
        //
        // @phpstan-ignore-next-line
        \iterator_to_array($result->{$method}());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function get_data_for_free(): iterable
    {
        $class   = new \ReflectionClass(Result::class);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if (\in_array($method->getName(), ['free', '__construct'], true)) {
                continue;
            }

            yield \sprintf('Method %s.', $method->getName()) => [$method->getName()];
        }
    }

    #[Test]
    public function supports_serialization(): void
    {
        $result     = new Result($this->connection->executeQuery('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id'));
        $serialized = \serialize($result);

        /** @var Result $unserialized */
        $unserialized = \unserialize($serialized, ['allowed_classes' => true]);

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], $unserialized->fetchAllAssociative());
    }

    /**
     * @param non-empty-string  $method
     * @param ?non-empty-string $configure
     */
    #[Test]
    #[DataProvider('get_proxying_methods')]
    public function wraps_vendor_exception(string $method, ?string $configure = null): void
    {
        $this->expectException(DriverException::class);

        $resultset = $this->createMock(DbalDriverResult::class);

        $resultset
            ->expects($this->once())
            ->method($configure ?? $method)
            ->willThrowException(new UnknownParameter('Vendor exception message.'));

        $result = new Result($resultset);

        $result->{$method}();
    }

    /**
     * @param non-empty-string  $method
     * @param ?non-empty-string $configure
     */
    #[Test]
    #[DataProvider('get_proxying_methods')]
    public function wraps_unknown_exception(string $method, ?string $configure = null): void
    {
        $this->expectException(RuntimeException::class);

        $resultset = $this->createMock(DbalDriverResult::class);

        $resultset
            ->expects($this->once())
            ->method($configure ?? $method)
            ->willThrowException(new \Exception('Unknown exception message.'));

        $result = new Result($resultset);

        $result->{$method}();
    }

    /**
     * @return iterable<string, array{non-empty-string}>
     */
    public static function get_proxying_methods(): iterable
    {
        yield 'Method fetchNumeric()' => ['fetchNumeric'];
        yield 'Method fetchAssociative()' => ['fetchAssociative'];
        yield 'Method fetchOne()' => ['fetchOne'];
        yield 'Method fetchAllNumeric()' => ['fetchAllNumeric'];
        yield 'Method fetchAllAssociative()' => ['fetchAllAssociative'];
        yield 'Method fetchFirstColumn()' => ['fetchFirstColumn'];
        yield 'Method rowCount()' => ['rowCount'];
        yield 'Method columnCount()' => ['columnCount'];
        yield 'Method __sleep()' => ['__sleep', 'columnCount'];
    }

    #[Test]
    public function free_ignores_exception(): void
    {
        $resultset = $this->createMock(DbalDriverResult::class);

        $resultset
            ->expects($this->once())
            ->method('free')
            ->willThrowException(new \Exception('Unknown exception message.'));

        $result = new Result($resultset);

        $result->free();
    }
}
