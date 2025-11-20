<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\OptionsInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Executor\ExecutorMiddleware;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Middleware\MiddlewareRegistry;

final class MiddlewareRegistryTest extends TestCase
{
    #[Test]
    public function build_query_chain(): void
    {
        $first   = $this->createMock(MiddlewareInterface::class);
        $second  = $this->createMock(MiddlewareInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $last    = new ExecutorMiddleware(new AdapterRegistry([$adapter]));

        $first
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): ResultInterface {
                return $next(\sprintf('first(%s)', $query), $context); // @phpstan-ignore-line
            });

        $second
            ->expects($this->once())
            ->method('query')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): ResultInterface {
                return $next(\sprintf('second(%s)', $query), $context); // @phpstan-ignore-line
            });

        $adapter
            ->expects($this->once())
            ->method('defaults')
            ->with(OptionsInterface::class)
            ->willReturn($this->createMock(OptionsInterface::class));

        $adapter
            ->expects($this->once())
            ->method('query')
            ->with('second(first(foo))')
            ->willReturn($this->createMock(ResultInterface::class));

        new MiddlewareRegistry([$first, $second, $last])->query('foo', new Context(source: 'foo'));
    }

    #[Test]
    public function build_statement_chain(): void
    {
        $first   = $this->createMock(MiddlewareInterface::class);
        $second  = $this->createMock(MiddlewareInterface::class);
        $adapter = $this->createMock(AdapterInterface::class);
        $last    = new ExecutorMiddleware(new AdapterRegistry([$adapter]));

        $first
            ->expects($this->once())
            ->method('statement')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): int {
                return $next(\sprintf('first(%s)', $query), $context); // @phpstan-ignore-line
            });

        $second
            ->expects($this->once())
            ->method('statement')
            ->willReturnCallback(function(string $query, ContextInterface $context, callable $next): int {
                return $next(\sprintf('second(%s)', $query), $context); // @phpstan-ignore-line
            });

        $adapter
            ->expects($this->once())
            ->method('defaults')
            ->with(OptionsInterface::class)
            ->willReturn($this->createMock(OptionsInterface::class));

        $adapter
            ->expects($this->once())
            ->method('statement')
            ->with('second(first(foo))')
            ->willReturn(0);

        new MiddlewareRegistry([$first, $second, $last])->statement('foo', new Context(source: 'foo'));
    }

    #[Test]
    public function wrong_last_middleware_configuration_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new MiddlewareRegistry([$this->createMock(MiddlewareInterface::class)]);
    }

    #[Test]
    public function unexhausted_context_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $adapter = $this->createMock(AdapterInterface::class);

        $adapter
            ->expects($this->once())
            ->method('defaults')
            ->with(OptionsInterface::class)
            ->willReturn($this->createMock(OptionsInterface::class));

        new MiddlewareRegistry([
            new ExecutorMiddleware(new AdapterRegistry([$adapter]))
        ])->statement('foo', new Context(
            source: 'foo',
            configurations: [new \stdClass()]
        ));
    }
}
