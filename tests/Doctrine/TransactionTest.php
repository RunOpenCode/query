<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine;

use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Transaction;

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
}