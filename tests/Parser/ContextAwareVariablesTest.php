<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Parser;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Doctrine\Parameters\Named;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\Context;
use RunOpenCode\Component\Query\Parser\ContextAwareVariables;
use RunOpenCode\Component\Query\Parser\Variables;

final class ContextAwareVariablesTest extends TestCase
{
    #[Test]
    public function offset_exists(): void
    {
        $variables = new ContextAwareVariables(new Context(), Variables::default([
            'foo' => 'bar',
        ]), null);

        $this->assertTrue($variables->offsetExists('foo'));
    }

    #[Test]
    public function offset_get(): void
    {
        $variables = new ContextAwareVariables(new Context(), Variables::default([
            'foo' => 'bar',
        ]), null);

        $this->assertSame('bar', $variables->offsetGet('foo'));
    }

    #[Test]
    public function iterates_and_counts(): void
    {
        $context   = new Context();
        $vars      = new Variables()->add('foo', 'bar');
        $params    = new Named()->add('baz', 'qux');
        $variables = new ContextAwareVariables($context, $vars, $params);

        $this->assertSame([
            'baz'        => 'qux',
            'foo'        => 'bar',
            'variables'  => $vars,
            'parameters' => $params,
            'context'    => $context,
        ], \iterator_to_array($variables));
        $this->assertCount(5, $variables);
    }

    #[Test]
    public function add_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->add('foo', 'bar');
    }

    #[Test]
    public function set_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->set('foo', 'bar');
    }

    #[Test]
    public function remove_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->remove('foo');
    }

    #[Test]
    public function merge_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->merge(['foo' => 'bar']);
    }

    #[Test]
    public function offset_set_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->offsetSet('foo', 'bar');
    }

    #[Test]
    public function offset_unset_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        new ContextAwareVariables(new Context(), null, null)->offsetUnset('foo');
    }
}
