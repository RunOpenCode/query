<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Configuration\ExecutionInterface;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Doctrine\Configuration\Transaction;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Middleware\QueryContext;
use RunOpenCode\Component\Query\Middleware\StatementContext;
use RunOpenCode\Component\Query\Middleware\TransactionContext;

final class ExecutorMiddlewareTest extends TestCase
{
    private AdapterInterface&MockObject $adapter;

    private ExecutorMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adapter = $this->createMock(AdapterInterface::class);

        $this
            ->adapter
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $this->middleware = new ExecutorMiddleware(new AdapterRegistry([
            $this->adapter,
        ]));
    }

    #[Test]
    public function query(): void
    {
        $expected = $this->createMock(ResultInterface::class);

        $this->adapter
            ->expects($this->once())
            ->method('query')
            ->with('bar', $this->isInstanceOf(ExecutionInterface::class), null)
            ->willReturn($expected);

        $actual = $this->middleware->query(
            'bar',
            new QueryContext(
                query: 'bar',
                execution: Dbal::connection('foo'),
            ),
            static fn(): null => null, // @phpstan-ignore-line
        );

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function statement(): void
    {
        $expected = $this->createMock(AffectedInterface::class);

        $this->adapter
            ->expects($this->once())
            ->method('statement')
            ->with('bar', $this->isInstanceOf(ExecutionInterface::class), null)
            ->willReturn($expected);

        $actual = $this->middleware->statement(
            'bar',
            new StatementContext(
                statement: 'bar',
                execution: Dbal::connection('foo'),
            ),
            static fn(): null => null, // @phpstan-ignore-line
        );

        $this->assertSame($expected, $actual);
    }

    #[Test]
    public function transaction_scope_violation_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $firstAdapter      = $this->createMock(AdapterInterface::class);
        $secondAdapter     = $this->createMock(AdapterInterface::class);
        $firstTransaction  = Transaction::connection('first');
        $secondTransaction = Transaction::connection('second');

        $firstAdapter
            ->method(PropertyHook::get('name'))
            ->willReturn('first');

        $secondAdapter
            ->method(PropertyHook::get('name'))
            ->willReturn('second');

        $middleware = new ExecutorMiddleware(new AdapterRegistry([
            $firstAdapter,
            $secondAdapter,
        ]));

        $transaction = new TransactionContext([
            $secondTransaction,
        ], new TransactionContext([
            $firstTransaction,
        ], null));

        $middleware->statement(
            'foo',
            new StatementContext('foo', new Dbal('first'), $transaction),
            static fn(): null => null // @phpstan-ignore-line
        );
    }
}
