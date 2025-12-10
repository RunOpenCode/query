<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Isolator;
use Doctrine\DBAL\ConnectionException as GenericDbalConnectionException;
use RunOpenCode\Component\Query\Exception\ConnectionException;
use RunOpenCode\Component\Query\Exception\IsolationException;

final class IsolatorTest extends TestCase
{
    private Connection&MockObject $connection;

    private Isolator $isolator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = $this->createMock(Connection::class);
        $this->isolator   = new Isolator('foo', $this->connection);
    }

    #[Test]
    public function isolate(): void
    {
        $captured = [];

        $this
            ->connection
            ->expects($this->exactly(2))
            ->method('getTransactionIsolation')
            ->willReturnOnConsecutiveCalls(
                TransactionIsolationLevel::READ_UNCOMMITTED,
                TransactionIsolationLevel::SERIALIZABLE,
            );

        $this
            ->connection
            ->expects($this->exactly(2))
            ->method('setTransactionIsolation')
            ->willReturnCallback(static function(TransactionIsolationLevel $isolation) use (&$captured): void {
                $captured[] = $isolation;
            });

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
        $this->isolator->revert();

        $this->assertSame([
            TransactionIsolationLevel::SERIALIZABLE,
            TransactionIsolationLevel::READ_UNCOMMITTED,
        ], $captured);
    }

    #[Test]
    public function skip_when_not_requested(): void
    {
        $this
            ->connection
            ->expects($this->never())
            ->method($this->anything());

        $this->isolator->isolate(null);
        $this->isolator->revert();
    }

    #[Test]
    public function skip_when_requested_equal_to_current(): void
    {
        $this
            ->connection
            ->expects($this->once())
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::SERIALIZABLE);

        $this
            ->connection
            ->expects($this->never())
            ->method('setTransactionIsolation');

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
        $this->isolator->revert();
    }

    #[Test]
    public function skip_revert_when_requested_equal_to_current(): void
    {
        $this
            ->connection
            ->expects($this->exactly(2))
            ->method('getTransactionIsolation')->willReturn(TransactionIsolationLevel::READ_UNCOMMITTED);

        $this
            ->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::SERIALIZABLE);

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
        $this->isolator->revert();
    }

    #[Test]
    public function get_isolation_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $this
            ->connection
            ->expects($this->once())
            ->method('getTransactionIsolation')
            ->willThrowException(new GenericDbalConnectionException());

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
    }

    #[Test]
    public function get_isolation_throws_isolation_exception(): void
    {
        $this->expectException(IsolationException::class);

        $this
            ->connection
            ->expects($this->once())
            ->method('getTransactionIsolation')
            ->willThrowException(new \RuntimeException());

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
    }

    #[Test]
    public function set_isolation_throws_connection_exception(): void
    {
        $this->expectException(ConnectionException::class);

        $this
            ->connection
            ->expects($this->once())
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::REPEATABLE_READ);

        $this
            ->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::SERIALIZABLE)
            ->willThrowException(new GenericDbalConnectionException());

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
    }

    #[Test]
    public function set_isolation_throws_isolation_exception(): void
    {
        $this->expectException(IsolationException::class);

        $this
            ->connection
            ->expects($this->once())
            ->method('getTransactionIsolation')
            ->willReturn(TransactionIsolationLevel::REPEATABLE_READ);

        $this
            ->connection
            ->expects($this->once())
            ->method('setTransactionIsolation')
            ->with(TransactionIsolationLevel::SERIALIZABLE)
            ->willThrowException(new \RuntimeException());

        $this->isolator->isolate(TransactionIsolationLevel::SERIALIZABLE);
    }
}
