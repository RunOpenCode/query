<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Middleware\Context;

final class ContextTest extends TestCase
{
    #[Test]
    public function peak(): void
    {
        $configuration = new \stdClass();
        $context       = new Context([$configuration]);

        $context->peak(\stdClass::class);
        
        $this->assertFalse($context->depleted());
        $this->assertSame([
            $configuration
        ], \iterator_to_array($context->unused()));
    }

    #[Test]
    public function require(): void
    {
        $context = new Context([new \stdClass()]);

        $context->require(\stdClass::class);

        $this->assertTrue($context->depleted());
    }

    #[Test]
    public function require_throws_exception_for_same_subject(): void
    {
        $this->expectException(LogicException::class);

        $context = new Context([new \stdClass()]);

        $context->require(\stdClass::class);
        $context->require(\stdClass::class);
    }
}