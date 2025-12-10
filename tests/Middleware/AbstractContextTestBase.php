<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Middleware;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Exception\LogicException;

abstract class AbstractContextTestBase extends TestCase
{
    #[Test]
    public function peak(): void
    {
        $configuration = new \stdClass();
        $context       = $this->createContext($configuration);

        $this->assertSame($configuration, $context->peak(\stdClass::class));
    }

    #[Test]
    public function require(): void
    {
        $configuration = new \stdClass();
        $context       = $this->createContext($configuration);

        $this->assertSame($configuration, $context->require(\stdClass::class));
    }

    #[Test]
    public function require_throws_exception_for_same_subject(): void
    {
        $this->expectException(LogicException::class);

        $context = $this->createContext(new \stdClass());

        $context->require(\stdClass::class);
        $context->require(\stdClass::class);
    }

    abstract protected function createContext(object ...$configurations): ContextInterface;
}
