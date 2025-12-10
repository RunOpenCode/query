<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\StatementMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Middleware\MiddlewareChain;
use RunOpenCode\Component\Query\Middleware\QueryContext;
use RunOpenCode\Component\Query\Middleware\StatementContext;

final class MiddlewareChainTest extends TestCase
{
    #[Test]
    public function build_query_chain(): void
    {
        $first   = $this->createMock(QueryMiddlewareInterface::class);
        $second  = $this->createMock(QueryMiddlewareInterface::class);
        $third   = $this->createMock(StatementMiddlewareInterface::class);
        $forth   = $this->createMock(TransactionMiddlewareInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $adapter
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $last = new ExecutorMiddleware(new AdapterRegistry([$adapter]));

        $first
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function(string $query, QueryContextInterface $context, callable $next): ResultInterface {
                return $next(\sprintf('first(%s)', $query), $context); // @phpstan-ignore-line
            });

        $second
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function(string $query, QueryContextInterface $context, callable $next): ResultInterface {
                return $next(\sprintf('second(%s)', $query), $context); // @phpstan-ignore-line
            });

        $third
            ->expects($this->never())
            ->method($this->anything());

        $forth
            ->expects($this->never())
            ->method($this->anything());

        new MiddlewareChain([$first, $second, $third, $forth, $last])->query(
            'foo',
            new QueryContext('foo', Dbal::connection('foo'))
        );
    }

    #[Test]
    public function build_statement_chain(): void
    {
        $first   = $this->createMock(StatementMiddlewareInterface::class);
        $second  = $this->createMock(StatementMiddlewareInterface::class);
        $third   = $this->createMock(QueryMiddlewareInterface::class);
        $forth   = $this->createMock(TransactionMiddlewareInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);

        $adapter
            ->expects($this->atLeastOnce())
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $last = new ExecutorMiddleware(new AdapterRegistry([$adapter]));

        $first
            ->expects($this->once())
            ->method('statement')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): AffectedInterface {
                return $next(\sprintf('first(%s)', $query), $context); // @phpstan-ignore-line
            });

        $second
            ->expects($this->once())
            ->method('statement')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): AffectedInterface {
                return $next(\sprintf('second(%s)', $query), $context); // @phpstan-ignore-line
            });

        $third
            ->expects($this->never())
            ->method($this->anything());

        $forth
            ->expects($this->never())
            ->method($this->anything());

        new MiddlewareChain([$first, $second, $third, $forth, $last])->statement(
            'foo',
            new StatementContext('foo', Dbal::connection('foo'))
        );
    }

    #[Test]
    public function build_transaction_chain(): void
    {
        $this->markTestIncomplete();
    }
}
