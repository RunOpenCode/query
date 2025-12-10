<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Contract\Parser\VariablesInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Middleware\QueryContext;
use RunOpenCode\Component\Query\Middleware\StatementContext;
use RunOpenCode\Component\Query\Parser\ContextAwareVariables;
use RunOpenCode\Component\Query\Parser\ParserMiddleware;
use RunOpenCode\Component\Query\Parser\ParserRegistry;
use RunOpenCode\Component\Query\Parser\Variables;

final class ParserMiddlewareTest extends TestCase
{
    private ParserInterface&MockObject $parser;

    private ParserMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser     = $this->createMock(ParserInterface::class);
        $this->middleware = new ParserMiddleware(new ParserRegistry([
            $this->parser,
        ]));

        $this
            ->parser
            ->expects($this->atMost(1))
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $this
            ->parser
            ->expects($this->atMost(1))
            ->method('supports')
            ->willReturn(true);
    }

    #[Test]
    public function parses_query(): void
    {
        $vars    = new Variables()->add('baz', 'qux');
        $params  = new Named()->add('foo', 'bar');
        $context = new QueryContext('foo', Dbal::connection('foo'), null, $params, $vars);

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')->willReturnCallback(function(string $query): string {
                $this->assertSame('foo', $query);
                return 'foo_parsed';
            });

        $this->middleware->query('foo', $context, fn(): ResultInterface => $this->createStub(ResultInterface::class));
    }

    #[Test]
    public function parses_statement(): void
    {
        $vars    = new Variables()->add('baz', 'qux');
        $params  = new Named()->add('foo', 'bar');
        $context = new StatementContext('foo', Dbal::connection('foo'), null, $params, $vars);

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')->willReturnCallback(function(string $query): string {
                $this->assertSame('foo', $query);
                return 'foo_parsed';
            });

        $this->middleware->statement('foo', $context, fn(): AffectedInterface => $this->createStub(AffectedInterface::class));
    }
}
