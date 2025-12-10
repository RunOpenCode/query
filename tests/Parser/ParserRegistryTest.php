<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Parser\ParserInterface;
use RunOpenCode\Component\Query\Exception\NotExistsException;
use RunOpenCode\Component\Query\Parser\ParserRegistry;
use RunOpenCode\Component\Query\Parser\Variables;

final class ParserRegistryTest extends TestCase
{
    private ParserInterface&MockObject $parser;

    private ParserRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->createMock(ParserInterface::class);

        $this
            ->parser
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $this->registry = new ParserRegistry([$this->parser]);
    }

    #[Test]
    public function uses_referenced_parser(): void
    {
        $variables = Variables::create(parser: 'foo');

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')
            ->with('bar', $variables)
            ->willReturn('parsed');

        $this->assertSame('parsed', $this->registry->parse('bar', $variables));
    }

    #[Test]
    public function uses_matching_parser(): void
    {
        $variables = Variables::default();

        $this
            ->parser
            ->expects($this->once())
            ->method('supports')
            ->with('bar')
            ->willReturn(true);

        $this
            ->parser
            ->expects($this->once())
            ->method('parse')
            ->with('bar', $variables)
            ->willReturn('parsed');

        $this->assertSame('parsed', $this->registry->parse('bar', $variables));
    }

    #[Test]
    public function throws_exception_when_referenced_parser_does_not_exists(): void
    {
        $this->expectException(NotExistsException::class);
        
        $this
            ->parser
            ->expects($this->never())
            ->method($this->anything());

        $this->registry->parse('foo', new Variables(parser: 'bar'));
    }

    #[Test]
    public function throws_exception_when_supporting_parser_does_not_exists(): void
    {
        $this->expectException(NotExistsException::class);

        $this
            ->parser
            ->expects($this->once())
            ->method('supports')
            ->with('foo')
            ->willReturn(false);

        $this->registry->parse('foo', Variables::default());
    }
}
