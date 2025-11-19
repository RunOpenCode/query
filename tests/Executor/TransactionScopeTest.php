<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Executor;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\ExecutionScope;
use RunOpenCode\Component\Query\Executor\TransactionScope;

final class TransactionScopeTest extends TestCase
{
    #[Test]
    public function scope_matching(): void
    {
        $first = $this->createMock(AdapterInterface::class);
        $second = $this->createMock(AdapterInterface::class);
        $third = $this->createMock(AdapterInterface::class);

        $first
            ->method(PropertyHook::get('name'))
            ->willReturn('first');

        $second
            ->method(PropertyHook::get('name'))
            ->willReturn('second');

        $third
            ->method(PropertyHook::get('name'))
            ->willReturn('third');

        $scope = new TransactionScope(
            [$third],
            new TransactionScope(
                [$second],
                new TransactionScope([$first])
            )
        );

        $this->assertFalse($scope->accepts('first', ExecutionScope::Strict));
        $this->assertTrue($scope->accepts('first', ExecutionScope::Parent));
        $this->assertTrue($scope->accepts('first', ExecutionScope::None));

        $this->assertFalse($scope->accepts('second', ExecutionScope::Strict));
        $this->assertTrue($scope->accepts('second', ExecutionScope::Parent));
        $this->assertTrue($scope->accepts('second', ExecutionScope::None));

        $this->assertTrue($scope->accepts('third', ExecutionScope::Strict));
        $this->assertTrue($scope->accepts('third', ExecutionScope::Parent));
        $this->assertTrue($scope->accepts('third', ExecutionScope::None));
    }
}
