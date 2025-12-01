<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Configuration;

use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Configuration\Transaction;

final class TransactionTest extends TestCase
{
    #[Test]
    #[TestWith(['readUncommitted', TransactionIsolationLevel::READ_UNCOMMITTED], 'Read uncommitted.')]
    #[TestWith(['readCommitted', TransactionIsolationLevel::READ_COMMITTED], 'Read committed.')]
    #[TestWith(['repeatableRead', TransactionIsolationLevel::REPEATABLE_READ], 'Repeatable read.')]
    #[TestWith(['serializable', TransactionIsolationLevel::SERIALIZABLE], 'Serializable.')]
    public function construct_with_isolation(string $method, TransactionIsolationLevel $expected): void
    {
        $configuration = Transaction::{$method}('foo');

        $this->assertInstanceOf(Transaction::class, $configuration);
        $this->assertSame($expected, $configuration->isolation);
    }

    #[Test]
    public function creates_for_connection(): void
    {
        $configuration = Transaction::connection('foo');

        $this->assertSame('foo', $configuration->connection);
    }

    #[Test]
    public function with_connection(): void
    {
        $configuration = Transaction::connection('foo');
        $modified      = $configuration->withConnection('bar');

        $this->assertNotSame($configuration, $modified);
        $this->assertNotSame($configuration->connection, $modified->connection);
        $this->assertSame('bar', $modified->connection);
    }

    #[Test]
    public function with_isolation(): void
    {
        $configuration = new Transaction('foo');
        $modified      = $configuration->withIsolation(TransactionIsolationLevel::SERIALIZABLE);

        $this->assertNotSame($configuration, $modified);
        $this->assertNotSame($configuration->isolation, $modified->isolation);
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $modified->isolation);
    }

    #[Test]
    #[TestWith(['withReadUncommitedIsolation', TransactionIsolationLevel::READ_UNCOMMITTED])]
    #[TestWith(['withRepeatableReadIsolation', TransactionIsolationLevel::REPEATABLE_READ])]
    #[TestWith(['withReadCommitedIsolation', TransactionIsolationLevel::READ_COMMITTED])]
    #[TestWith(['withSerializableIsolation', TransactionIsolationLevel::SERIALIZABLE])]
    public function with_isolation_method(string $method, TransactionIsolationLevel $expected): void
    {
        $configuration = new Transaction('foo');
        $modified      = $configuration->{$method}();

        $this->assertNotSame($modified, $configuration);
        $this->assertInstanceOf(Transaction::class, $modified);
        $this->assertSame($expected, $modified->isolation);
    }
}
