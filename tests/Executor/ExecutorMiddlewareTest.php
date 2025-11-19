<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Executor\TransactionScope;
use RunOpenCode\Component\Query\Middleware\Context;

final class ExecutorMiddlewareTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;

    private ExecutorMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter    = $this->createMock(AdapterInterface::class);
        $this->middleware = new ExecutorMiddleware(new AdapterRegistry([
            $this->adapter,
        ]));
    }

    #[Test]
    public function query(): void
    {
        $this->adapter
            ->expects($this->once())
            ->method('query')
            ->with('foo', null, null)
            ->willReturn($this->createMock(ResultInterface::class));

        // @phpstan-ignore-next-line
        $this->middleware->query('foo', new Context(source: 'foo'), static fn(): null => null);
    }

    #[Test]
    public function statement(): void
    {
        $this->adapter
            ->expects($this->once())
            ->method('statement')
            ->with('foo', null, null)
            ->willReturn(1);

        // @phpstan-ignore-next-line
        $this->middleware->statement('foo', new Context(source: 'foo'), static fn(): null => null);
    }

    #[Test]
    public function transaction_scope_violation_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $first  = $this->createMock(AdapterInterface::class);
        $second = $this->createMock(AdapterInterface::class);

        $first
            ->method(PropertyHook::get('name'))
            ->willReturn('first');

        $second
            ->method(PropertyHook::get('name'))
            ->willReturn('second');

        $middleware = new ExecutorMiddleware(new AdapterRegistry([
            $first,
            $second,
        ]));

        $transaction = new TransactionScope(
            [$second],
            new TransactionScope([$first])
        );

        // @phpstan-ignore-next-line
        $middleware->statement('foo', new Context(source: 'foo', transaction: $transaction), static fn(): null => null);
    }
}
