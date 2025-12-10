<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\DbalDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\DatasetInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\ResultClosedException;
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

    private function execute(string $query): Result
    {
        return new Result(new DbalDataset('default', $this->connection->executeQuery($query)));
    }

    #[Test]
    #[DataProvider('get_data_for_scalar')]
    public function scalar(string $query, mixed $expected): void
    {
        $this->assertSame($expected, $this->execute($query)->scalar());
    }

    /**
     * @return iterable<string, array{string, scalar}>
     */
    public static function get_data_for_scalar(): iterable
    {
        yield 'One column, one row, int result.' => ['SELECT id FROM test WHERE id = 1', 1];
        yield 'Multiple columns, one row, int result.' => ['SELECT id, title FROM test WHERE id = 1', 1];
        yield 'One column, one row, string result.' => ['SELECT title FROM test WHERE id = 3', 'Title 3'];
    }

    #[Test]
    public function scalar_returns_default_when_no_results(): void
    {
        $this->assertSame(42, $this->execute('SELECT id FROM test WHERE id = -1')->scalar(42));
    }

    #[Test]
    public function scalar_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $this->execute('SELECT id FROM test WHERE id = -1')->scalar();
    }

    #[Test]
    public function scalar_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $this->execute('SELECT id FROM test')->scalar();
    }

    #[Test]
    #[DataProvider('get_data_for_vector')]
    public function vector(string $query, mixed $expected): void
    {
        $this->assertSame($expected, $this->execute($query)->vector());
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function get_data_for_vector(): iterable
    {
        yield 'One column, int result.' => ['SELECT id FROM test ORDER BY id', [1, 2, 3, 4, 5]];
        yield 'Multiple columns, int result.' => ['SELECT id, title FROM test ORDER BY id', [1, 2, 3, 4, 5]];
        yield 'One column, string result.' => ['SELECT title FROM test ORDER BY id', ['Title 1', 'Title 2', 'Title 3', 'Title 4', 'Title 5']];
        yield 'One column, no results.' => ['SELECT title FROM test WHERE id = -1', []];
    }

    #[Test]
    public function vector_returns_default_when_no_results(): void
    {
        $this->assertSame(['foo', 'bar'], $this->execute('SELECT id FROM test WHERE id = -1')->vector(['foo', 'bar']));
    }

    #[Test]
    public function record(): void
    {
        $this->assertSame(['id' => 1, 'title' => 'Title 1'], $this->execute('SELECT id, title FROM test WHERE id = 1')->record());
    }

    #[Test]
    public function record_returns_default_when_no_results(): void
    {
        $this->assertSame(['foo', 'bar'], $this->execute('SELECT * FROM test WHERE id = -1')->record(['foo', 'bar']));
    }

    #[Test]
    public function record_throws_exception_when_resultset_is_empty(): void
    {
        $this->expectException(NoResultException::class);

        $this->execute('SELECT * FROM test WHERE id = -1')->record();
    }

    #[Test]
    public function record_throws_exception_when_resultset_has_more_than_one_row(): void
    {
        $this->expectException(NonUniqueResultException::class);

        $this->execute('SELECT * FROM test')->record();
    }

    #[Test]
    #[TestWith(['scalar'], 'Method scalar()')]
    #[TestWith(['vector'], 'Method vector()')]
    #[TestWith(['record'], 'Method record()')]
    public function methods_with_variadic_defaults_throw_exception_on_multiple_default_values(string $method): void
    {
        $this->expectException(InvalidArgumentException::class);

        $result = new Result(new ArrayDataset('default', []));
        $result->{$method}('foo', 'bar');
    }

    #[Test]
    public function iterates(): void
    {
        $result  = $this->execute('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id');
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
        $this->expectException(ResultClosedException::class);

        $result = $this->execute('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id');

        $result->free();

        // A small trick applied, method will either throw ResultClosedException, or,
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
        $result     = $this->execute('SELECT id, title FROM test WHERE id IN (1, 2) ORDER BY id');
        $serialized = \serialize($result);

        /** @var Result $unserialized */
        $unserialized = \unserialize($serialized, ['allowed_classes' => true]);

        // We expect for result set not to be closed when serialized.
        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], \iterator_to_array($result));

        // We deserialized result set to yield same results as original.
        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1'],
            ['id' => 2, 'title' => 'Title 2'],
        ], \iterator_to_array($unserialized));
    }

    public function serialization_of_closed_result_set_throws_exception(): void
    {
        $this->expectException(ResultClosedException::class);

        $result = $this->execute('SELECT * FROM test');

        \iterator_to_array($result);

        \serialize($result);
    }

    #[Test]
    public function free_ignores_exception(): void
    {
        $dataset = $this->createMock(DatasetInterface::class);
        
        $dataset
            ->expects($this->once())
            ->method(PropertyHook::get('connection'))
            ->willReturn('default');
        
        $dataset
            ->expects($this->once())
            ->method('free')
            ->willThrowException(new \Exception('Unknown exception message.'));

        $result = new Result($dataset);

        $result->free();
    }
}
