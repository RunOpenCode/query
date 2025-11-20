<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception\DeadlockException as DbalDeadlockException;
use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\PreCondition;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Adapter;
use RunOpenCode\Component\Query\Doctrine\Dbal\Options;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Doctrine\Parameters\Positional;
use RunOpenCode\Component\Query\Doctrine\Transaction;
use RunOpenCode\Component\Query\Exception\BeginTransactionException;
use RunOpenCode\Component\Query\Exception\CommitTransactionException;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\DeadlockException;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\RollbackTransactionException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Exception\SyntaxException;
use Doctrine\DBAL\ConnectionException as GenericDbalConnectionException;
use RunOpenCode\Component\Query\Tests\Fixtures\Dbal\MySqlDatabase;
use RunOpenCode\Component\Query\Tests\PHPUnit\DbalTools;

final class AdapterTest extends TestCase
{
    use DbalTools;

    private Connection $connection;

    private Adapter $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createMySqlConnection(MySqlDatabase::Foo);
        $this->adapter    = new Adapter('foo', $this->connection);
    }

    #[PreCondition]
    protected function dataset_ready(): void
    {
        $this->assertCount(5, $this->adapter->query('SELECT * FROM test', new Options()));
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function commit_transaction(): void
    {
        $this->adapter->begin(new Transaction($this->adapter->name));

        $affected  = $this->adapter->statement('DELETE FROM test', new Options());
        $available = $this->adapter->query('SELECT COUNT(*) AS cnt FROM test', new Options())->getScalar();

        $this->adapter->commit();

        $this->assertSame(5, $affected);
        $this->assertSame(0, $available);

        $actual = $this->adapter->query('SELECT COUNT(*) AS cnt FROM test', new Options())->getScalar();

        $this->assertSame(0, $actual);
    }

    #[Test]
    public function rollback_transaction(): void
    {
        $this->adapter->begin(new Transaction($this->adapter->name));

        $affected  = $this->adapter->statement('DELETE FROM test', new Options());
        $available = $this->adapter->query('SELECT COUNT(*) AS cnt FROM test', new Options())->getScalar();

        $this->adapter->rollback();

        $this->assertSame(5, $affected);
        $this->assertSame(0, $available);

        $actual = $this->adapter->query('SELECT COUNT(*) AS cnt FROM test', new Options())->getScalar();

        $this->assertSame(5, $actual);
    }

    #[Test]
    public function committed_transaction_with_custom_isolation(): void
    {
        $this->adapter->begin(Transaction::serializable($this->adapter->name));

        $this->assertSame(5, $this->adapter->statement('DELETE FROM test', new Options()));
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $this->connection->getTransactionIsolation());

        $this->adapter->commit();

        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function rolled_back_transaction_with_custom_isolation(): void
    {
        $this->adapter->begin(Transaction::serializable($this->adapter->name));

        $this->assertSame(5, $this->adapter->statement('DELETE FROM test', new Options()));
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $this->connection->getTransactionIsolation());

        $this->adapter->rollback();

        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function transaction_isolation_skipped_when_current_isolation_level_matches_requested(): void
    {
        $this->connection->setTransactionIsolation(TransactionIsolationLevel::SERIALIZABLE);

        $this->adapter->begin(Transaction::serializable($this->adapter->name));

        $this->assertSame(5, $this->adapter->statement('DELETE FROM test', new Options()));
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $this->connection->getTransactionIsolation());

        $this->adapter->rollback();

        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function query_without_parameters(): void
    {
        $result = $this->adapter->query('SELECT * FROM test WHERE id = 1', new Options());

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1', 'description' => 'Description 1'],
        ], $result->fetchAllAssociative());
    }

    #[Test]
    public function query_with_named_parameters(): void
    {
        $result = $this->adapter->query(
            'SELECT * FROM test WHERE id = :id',
            new Options(),
            new Named()
                ->integer('id', 1)
        );

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1', 'description' => 'Description 1'],
        ], $result->fetchAllAssociative());
    }

    #[Test]
    public function query_with_positional_parameters(): void
    {
        $result = $this->adapter->query(
            'SELECT * FROM test WHERE id = ?',
            new Options(),
            new Positional()
                ->integer(1)
        );

        $this->assertSame([
            ['id' => 1, 'title' => 'Title 1', 'description' => 'Description 1'],
        ], $result->fetchAllAssociative());
    }

    #[Test]
    public function statement_with_named_parameters(): void
    {
        $this->assertCount(0, $this->adapter->query('SELECT * FROM test WHERE id = 42', new Options()));

        $affected = $this->adapter->statement(
            'INSERT INTO test (id, title, description) VALUES  (:id, :title, :description)',
            new Options(),
            new Named()
                ->integer('id', 42)
                ->string('title', 'foo')
                ->string('description', 'bar'),
        );

        $this->assertSame(1, $affected);
        $this->assertSame([
            ['id' => 42, 'title' => 'foo', 'description' => 'bar'],
        ], $this->adapter->query('SELECT * FROM test WHERE id = 42', new Options())->fetchAllAssociative());
    }

    #[Test]
    public function statement_with_positional_parameters(): void
    {
        $this->assertCount(0, $this->adapter->query('SELECT * FROM test WHERE id IN (42, 43)', new Options()));

        $affected = $this->adapter->statement(
            'INSERT INTO test (id, title, description) VALUES  (?, ?, ?), (?, ?, ?)',
            new Options(),
            new Positional()
                ->integer(42)
                ->string('foo')
                ->string('bar')
                ->integer(43)
                ->string('baz')
                ->string('qux'),
        );

        $this->assertSame(2, $affected);
        $this->assertSame([
            ['id' => 42, 'title' => 'foo', 'description' => 'bar'],
            ['id' => 43, 'title' => 'baz', 'description' => 'qux'],
        ], $this->adapter->query('SELECT * FROM test WHERE id IN(42, 43) ORDER BY id', new Options())->fetchAllAssociative());
    }

    #[Test]
    public function query_with_custom_isolation(): void
    {
        $this->adapter->query('SELECT * FROM test', Options::serializable());

        $this->assertSqlLogSame([
            'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            'START TRANSACTION',
            'SELECT * FROM test',
            'COMMIT',
            'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
        ], $this->connection);

        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function statement_with_custom_isolation(): void
    {
        $this->adapter->statement('DELETE FROM test WHERE id = 1', Options::serializable());

        $this->assertSqlLogSame([
            'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE',
            'START TRANSACTION',
            'DELETE FROM test WHERE id = 1',
            'COMMIT',
            'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
        ], $this->connection);

        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function query_isolation_skipped_when_current_isolation_level_matches_requested(): void
    {
        $this->connection->setTransactionIsolation(TransactionIsolationLevel::SERIALIZABLE);
        $this->clearAllDbalLogs();

        $this->adapter->query('SELECT * FROM test', Options::serializable());

        $this->assertSqlLogSame([
            'SELECT * FROM test',
        ], $this->connection);

        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $this->connection->getTransactionIsolation());
    }

    #[Test]
    public function begin_transaction_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $adapter = new Adapter('foo', DriverManager::getConnection([
            'driver'   => 'mysqli',
            'dbname'   => MySqlDatabase::Foo->value,
            'user'     => 'foo',
            'password' => 'foo',
            'host'     => 'mysql.local',
        ]));

        $adapter->begin(new Transaction($adapter->name));
    }

    #[Test]
    public function begin_transaction_throws_transaction_exception(): void
    {
        $this->expectException(BeginTransactionException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method('beginTransaction')
            ->willThrowException(new \Exception());

        $adapter->begin(new Transaction($adapter->name));
    }

    #[Test]
    public function commit_transaction_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method('beginTransaction');

        $connection
            ->expects($this->once())
            ->method('commit')
            ->willThrowException(new GenericDbalConnectionException());

        $adapter->begin(new Transaction($adapter->name));
        $adapter->commit();
    }

    #[Test]
    public function commit_transaction_throws_transaction_exception(): void
    {
        $this->expectException(CommitTransactionException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method('beginTransaction');

        $connection
            ->expects($this->once())
            ->method('commit')
            ->willThrowException(new \Exception());

        $adapter->begin(new Transaction($adapter->name));
        $adapter->commit();
    }

    #[Test]
    public function rollback_transaction_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method('beginTransaction');

        $connection
            ->expects($this->once())
            ->method('rollBack')
            ->willThrowException(new GenericDbalConnectionException());

        $adapter->begin(new Transaction($adapter->name));
        $adapter->rollback();
    }

    #[Test]
    public function rollback_transaction_throws_transaction_exception(): void
    {
        $this->expectException(RollbackTransactionException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method('beginTransaction');

        $connection
            ->expects($this->once())
            ->method('rollBack')
            ->willThrowException(new \Exception());

        $adapter->begin(new Transaction($adapter->name));
        $adapter->rollback();
    }

    #[Test]
    public function query_throws_syntax_exception(): void
    {
        $this->expectException(SyntaxException::class);

        $this->adapter->query('FOO', new Options());
    }

    #[Test]
    public function query_throws_driver_exception(): void
    {
        $this->expectException(DriverException::class);

        $this->adapter->query('SELECT * FROM foo', new Options());
    }

    #[Test]
    public function query_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $adapter = new Adapter('foo', DriverManager::getConnection([
            'driver'   => 'mysqli',
            'dbname'   => MySqlDatabase::Foo->value,
            'user'     => 'foo',
            'password' => 'foo',
            'host'     => 'mysql.local',
        ]));

        $adapter->query('SELECT * FROM test', new Options());
    }

    #[Test]
    public function query_throws_deadlock_exception(): void
    {
        $this->expectException(DeadlockException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method($this->anything())
            ->willThrowException(new DbalDeadlockException(
                $this->createMock(DbalDriverException::class),
                null,
            ));

        $adapter->query('SELECT * FROM test', new Options());
    }

    #[Test]
    public function query_throws_runtime_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $connection = $this->createMock(Connection::class);
        $adapter    = new Adapter('foo', $connection);

        $connection
            ->expects($this->once())
            ->method($this->anything())
            ->willThrowException(new \RuntimeException('Some runtime error'));

        $adapter->query('SELECT * FROM test', new Options());
    }
}
