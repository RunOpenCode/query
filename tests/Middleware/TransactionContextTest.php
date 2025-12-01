<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use PHPUnit\Framework\Attributes\Test;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionScope;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Transaction;
use RunOpenCode\Component\Query\Middleware\TransactionContext;

final class TransactionContextTest extends AbstractContextTestBase
{
    #[Test]
    public function execution_scope_matching(): void
    {
        $first  = Transaction::connection('first');
        $second = Transaction::connection('second');
        $third  = Transaction::connection('third');

        $context = new TransactionContext(
            [$third],
            new TransactionContext(
                [$second],
                new TransactionContext([$first], null)
            )
        );

        $this->assertFalse($context->accepts(ExecutionScope::Strict, 'first'));
        $this->assertTrue($context->accepts(ExecutionScope::Parent, 'first'));
        $this->assertTrue($context->accepts(ExecutionScope::None, 'first'));

        $this->assertFalse($context->accepts(ExecutionScope::Strict, 'second'));
        $this->assertTrue($context->accepts(ExecutionScope::Parent, 'second'));
        $this->assertTrue($context->accepts(ExecutionScope::None, 'second'));

        $this->assertTrue($context->accepts(ExecutionScope::Strict, 'third'));
        $this->assertTrue($context->accepts(ExecutionScope::Parent, 'third'));
        $this->assertTrue($context->accepts(ExecutionScope::None, 'third'));
    }

    protected function createContext(object ...$configurations): ContextInterface
    {
        return new TransactionContext([new Transaction('foo')], null, ...$configurations);
    }
}
