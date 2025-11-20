<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal;

use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;
use RunOpenCode\Component\Query\Doctrine\Dbal\Options;

final class OptionsTest extends TestCase
{
    #[Test]
    public function creates_isolated(): void
    {
        $this->assertSame(TransactionIsolationLevel::READ_COMMITTED, Options::readCommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::READ_UNCOMMITTED, Options::readUncommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, Options::repeatableRead()->isolation);
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, Options::serializable()->isolation);
    }

    #[Test]
    public function creates_for_connection(): void
    {
        $this->assertSame(
            'foo',
            Options::connection('foo')->connection,
        );
    }

    #[Test]
    public function with_connection(): void
    {
        $options  = new Options(
            connection: 'foo',
            isolation: TransactionIsolationLevel::REPEATABLE_READ,
            scope: ExecutionScope::Parent,
        );
        $modified = $options->withConnection('bar');

        $this->assertNotSame($modified, $options);
        $this->assertSame('bar', $modified->connection);
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, $modified->isolation);
        $this->assertSame(ExecutionScope::Parent, $modified->scope);
    }
}
