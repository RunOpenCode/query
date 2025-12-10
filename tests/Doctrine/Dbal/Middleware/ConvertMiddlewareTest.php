<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Doctrine\Dbal\Middleware;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Runtime\PropertyHook;
use PHPUnit\Framework\TestCase;
use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Doctrine\Dbal\Adapter;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Convert;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\Converted;
use RunOpenCode\Component\Query\Doctrine\Dbal\Middleware\ConvertMiddleware;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Executor\AdapterRegistry;
use RunOpenCode\Component\Query\Middleware\QueryContext;

final class ConvertMiddlewareTest extends TestCase
{
    private ConvertMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $connection       = $this->createStub(Connection::class);
        $adapter          = $this->createStub(AdapterInterface::class);
        
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($this->createStub(AbstractPlatform::class));

        $adapter
            ->method(PropertyHook::get('name'))
            ->willReturn('foo');

        $this->middleware = new ConvertMiddleware(new AdapterRegistry([
            new Adapter('default', $connection),
            $adapter,
        ]));
    }

    #[Test]
    public function skips_conversion_when_not_requested(): void
    {
        $result = new Result(new ArrayDataset('default', []));
        $next   = static fn(): ResultInterface => $result;

        $this->assertSame(
            $result,
            $this->middleware->query(
                'SELECT 1',
                new QueryContext('SELECT 1', Dbal::connection('default')),
                $next,
            )
        );
    }

    #[Test]
    public function provides_conversion_results(): void
    {
        $result = new Result(new ArrayDataset('default', []));
        $next   = static fn(): ResultInterface => $result;

        $this->assertInstanceOf(
            Converted::class,
            $this->middleware->query(
                'SELECT 1',
                new QueryContext('SELECT 1', Dbal::connection('default'), null, new Convert()->add('foo', 'bar')),
                $next,
            )
        );
    }

    #[Test]
    public function throws_exception_when_invalid_resultset_yielded(): void
    {
        $this->expectException(LogicException::class);

        $result = new Result(new ArrayDataset('foo', []));
        $next   = static fn(): ResultInterface => $result;

        $this->middleware->query(
            'SELECT 1',
            new QueryContext('SELECT 1', Dbal::connection('default'), null, new Convert()->add('foo', 'bar')),
            $next,
        );
    }

    #[Test]
    public function throws_exception_when_configuration_is_empty(): void
    {
        $this->expectException(LogicException::class);

        $result = new Result(new ArrayDataset('default', []));
        $next   = static fn(): ResultInterface => $result;

        $this->middleware->query(
            'SELECT 1',
            new QueryContext('SELECT 1', Dbal::connection('default'), null, new Convert()),
            $next,
        );
    }
}
