<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\RuntimeException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;

final class AdapterRegistryTest extends TestCase
{
    #[Test]
    public function get_default_adapter(): void
    {
        $first = $this->createMock(AdapterInterface::class);
        $second = $this->createMock(AdapterInterface::class);

        $first
            ->method(PropertyHook::get('name'))
            ->willReturn('first');

        $second
            ->method(PropertyHook::get('name'))
            ->willReturn('second');

        $registry = new AdapterRegistry([
            $first,
            $second,
        ]);

        $this->assertSame($first, $registry->get());
    }

    #[Test]
    public function get_requested_adapter(): void
    {
        $first = $this->createMock(AdapterInterface::class);
        $second = $this->createMock(AdapterInterface::class);

        $first
            ->method(PropertyHook::get('name'))
            ->willReturn('first');

        $second
            ->method(PropertyHook::get('name'))
            ->willReturn('second');

        $registry = new AdapterRegistry([
            $first,
            $second,
        ]);

        $this->assertSame($second, $registry->get('second'));
    }

    #[Test]
    public function registering_adapter_with_same_connection_name_throws_exception(): void
    {
        $this->expectException(LogicException::class);

        $adapter = $this->createMock(AdapterInterface::class);

        $adapter
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        new AdapterRegistry([
            $adapter,
            $adapter,
        ]);
    }

    #[Test]
    public function requesting_nonexisting_adapter_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);

        $adapter = $this->createMock(AdapterInterface::class);

        $adapter
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        new AdapterRegistry([$adapter])->get('bar');
    }
}
