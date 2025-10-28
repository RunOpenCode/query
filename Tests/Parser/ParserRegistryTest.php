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
        $this
            ->parser
            ->expects($this->once())
            ->method('parse')
            ->with('bar', [])
            ->willReturn('parsed');

        $this->assertSame('parsed', $this->registry->parse('bar', [], 'foo'));
    }

    #[Test]
    public function uses_matching_parser(): void
    {
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
            ->with('bar', [])
            ->willReturn('parsed');

        $this->assertSame('parsed', $this->registry->parse('bar', []));
    }

    #[Test]
    public function throws_exception_when_referenced_parser_does_not_exists(): void
    {
        $this->expectException(NotExistsException::class);

        $this->registry->parse('foo', [], 'unknown');
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

        $this->registry->parse('foo', []);
    }
}
