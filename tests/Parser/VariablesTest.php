<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Parser\TwigParser;
use RunOpenCode\Component\Query\Parser\Variables;
use RunOpenCode\Component\Query\Parser\VoidParser;

final class VariablesTest extends TestCase
{
    #[Test]
    public function creates_variables_bag_for_default_parser(): void
    {
        $this->assertNull(Variables::default()->parser);
    }

    #[Test]
    public function creates_variables_bag_for_void_parser(): void
    {
        $this->assertSame(VoidParser::NAME, Variables::void()->parser);
    }

    #[Test]
    public function creates_variables_bag_for_twig_parser(): void
    {
        $this->assertSame(TwigParser::NAME, Variables::twig()->parser);
    }

    #[Test]
    public function add(): void
    {
        $bag = Variables::default();

        $bag->add('foo', 'bar');

        $this->assertSame([
            'foo' => 'bar',
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function add_throws_exception_if_variable_defined(): void
    {
        $this->expectException(LogicException::class);

        $bag = Variables::default();

        $bag->add('foo', 'bar');
        $bag->add('foo', 'bar');
    }

    #[Test]
    public function set(): void
    {
        $bag = Variables::default();

        $bag->set('foo', 'bar');

        $this->assertSame([
            'foo' => 'bar',
        ], \iterator_to_array($bag));

        $bag->set('foo', 'baz');

        $this->assertSame([
            'foo' => 'baz',
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function remove(): void
    {
        $bag = Variables::default();

        $bag->set('foo', 'bar');

        $this->assertSame([
            'foo' => 'bar',
        ], \iterator_to_array($bag));

        $bag->remove('foo');

        $this->assertSame([], \iterator_to_array($bag));
    }

    #[Test]
    public function merge_without_overwrite(): void
    {
        $bag = Variables::default(['foo' => 'bar'])
                        ->merge(Variables::default(['baz' => 'qux']), false);

        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function merge_with_overwrite(): void
    {
        $bag = Variables::default(['foo' => 'bar'])
                        ->merge(Variables::default(['foo' => 'baz']));

        $this->assertSame([
            'foo' => 'baz',
        ], \iterator_to_array($bag));
    }

    #[Test]
    public function strict_merge_throws_exception_on_overwrite(): void
    {
        $this->expectException(LogicException::class);

        Variables::default(['foo' => 'bar'])->merge(Variables::default(['foo' => 'baz']), false);
    }

    #[Test]
    public function magic_properties(): void
    {
        $bag = Variables::default(['foo' => 'bar']);

        $this->assertTrue(isset($bag->foo));
        $this->assertSame('bar', $bag->foo);

        // @phpstan-ignore-next-line
        $bag->baz = 'qux';

        $this->assertTrue(isset($bag->baz));
        $this->assertSame('qux', $bag->baz);
    }

    #[Test]
    public function array_access(): void
    {
        $bag = Variables::default(['foo' => 'bar']);

        $this->assertTrue(isset($bag['foo']));
        $this->assertSame('bar', $bag['foo']);
        
        $bag['baz'] = 'qux';

        $this->assertTrue(isset($bag['baz']));
        $this->assertSame('qux', $bag['baz']);
        
        unset($bag['baz']);

        $this->assertFalse(isset($bag['baz']));
    }
    
    #[Test]
    public function iterates_and_counts(): void
    {
        $bag = Variables::default([
            'foo' => 'bar',
            'baz' => 'qux',
        ]);
        
        $this->assertSame([
            'foo' => 'bar',
            'baz' => 'qux',
        ], \iterator_to_array($bag));
        $this->assertCount(2, $bag);
    }
}
