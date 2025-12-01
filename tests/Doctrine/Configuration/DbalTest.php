<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Configuration;

use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;

final class DbalTest extends TestCase
{
    #[Test]
    public function creates_isolated(): void
    {
        $this->assertSame(TransactionIsolationLevel::READ_COMMITTED, Dbal::readCommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::READ_UNCOMMITTED, Dbal::readUncommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, Dbal::repeatableRead()->isolation);
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, Dbal::serializable()->isolation);
    }

    #[Test]
    public function creates_for_connection(): void
    {
        $this->assertSame(
            'foo',
            Dbal::connection('foo')->connection,
        );
    }

    #[Test]
    public function with_connection(): void
    {
        $configuration = new Dbal(
            connection: 'foo',
            isolation: TransactionIsolationLevel::REPEATABLE_READ,
            scope: ExecutionScope::Parent,
        );
        $modified      = $configuration->withConnection('bar');

        $this->assertNotSame($modified, $configuration);
        $this->assertSame('bar', $modified->connection);
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $modified->isolation);
        $this->assertSame(ExecutionScope::Parent, $modified->scope);
    }

    #[Test]
    public function with_execution_scope(): void
    {
        $configuration = new Dbal('foo');

        $this->assertNull($configuration->scope);

        $modified = $configuration->withExecutionScope(ExecutionScope::Parent);

        $this->assertNotSame($modified, $configuration);
        $this->assertSame(ExecutionScope::Parent, $modified->scope);
    }

    #[Test]
    public function with_isolation(): void
    {
        $configuration = new Dbal('foo');

        $this->assertNull($configuration->isolation);

        $modified = $configuration->withIsolation(TransactionIsolationLevel::SERIALIZABLE);

        $this->assertNotSame($modified, $configuration);
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, $modified->isolation);
    }

    #[Test]
    #[TestWith(['withReadUncommitedIsolation', TransactionIsolationLevel::READ_UNCOMMITTED])]
    #[TestWith(['withRepeatableReadIsolation', TransactionIsolationLevel::REPEATABLE_READ])]
    #[TestWith(['withReadCommitedIsolation', TransactionIsolationLevel::READ_COMMITTED])]
    #[TestWith(['withSerializableIsolation', TransactionIsolationLevel::SERIALIZABLE])]
    public function with_isolation_method(string $method, TransactionIsolationLevel $expected): void
    {
        $configuration = new Dbal('foo');
        $modified      = $configuration->{$method}();

        $this->assertNotSame($modified, $configuration);
        $this->assertInstanceOf(Dbal::class, $modified);
        $this->assertSame($expected, $modified->isolation);
    }
}
