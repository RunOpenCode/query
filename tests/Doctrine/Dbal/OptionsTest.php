<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal;

use Doctrine\DBAL\TransactionIsolationLevel;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Dbal\Options;

final class OptionsTest extends TestCase
{
    #[Test]
    public function has_tag(): void
    {
        $options = new Options(tags: ['foo']);

        $this->assertTrue($options->has('foo'));
        $this->assertFalse($options->has('bar'));
    }

    #[Test]
    public function creates_isolated(): void
    {
        $this->assertSame(TransactionIsolationLevel::READ_COMMITTED, Options::readCommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::READ_UNCOMMITTED, Options::readUncommitted()->isolation);
        $this->assertSame(TransactionIsolationLevel::REPEATABLE_READ, Options::repeatableRead()->isolation);
        $this->assertSame(TransactionIsolationLevel::SERIALIZABLE, Options::serializable()->isolation);
    }
}
