<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Cache;

use Doctrine\DBAL\Cache\ArrayResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use RunOpenCode\Component\Query\Cache\CacheIdentity;
use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use RunOpenCode\Component\Query\Cache\Invalidate;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Configuration\Dbal;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Exception\UnsupportedException;
use RunOpenCode\Component\Query\Middleware\QueryContext;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class CacheMiddlewareTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $cache;

    private CacheMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache      = $this->createMock(CacheItemPoolInterface::class);
        $this->middleware = new CacheMiddleware($this->cache);
    }

    #[Test]
    public function caches_query(): void
    {
        $expected = $this->createStub(ResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new QueryContext(
            query: 'foo',
            execution: new Dbal('default'),
            middlewares: CacheIdentity::static('key'),
        );

        $this
            ->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('key')
            ->willReturn($item);

        $item
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this
            ->cache
            ->expects($this->once())
            ->method('save')
            ->with($item);

        $this
            ->cache
            ->expects($this->never())
            ->method('deleteItem');

        $result = $this->middleware->query('foo', $context, fn(): ResultInterface => $expected);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function provides_cached_query_result(): void
    {
        $expected = $this->createStub(ResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new QueryContext(
            query: 'foo',
            execution: new Dbal('default'),
            middlewares: CacheIdentity::static('key'),
        );

        $this
            ->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('key')
            ->willReturn($item);

        $item
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(true);

        $item
            ->expects($this->once())
            ->method('get')
            ->willReturn($expected);

        $result = $this->middleware->query('foo', $context, fn(): never => $this->fail('Middleware chain should be interrupted.'));

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function skips_query_caching_when_instructed(): void
    {
        $expected = $this->createStub(ResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new QueryContext(
            query: 'foo',
            execution: new Dbal('default'),
            middlewares: new CacheIdentity('key', static fn(): false => false),
        );

        $this
            ->cache
            ->expects($this->once())
            ->method('getItem')
            ->with('key')
            ->willReturn($item);

        $item
            ->expects($this->once())
            ->method('isHit')
            ->willReturn(false);

        $this
            ->cache
            ->expects($this->never())
            ->method('save');

        $this
            ->cache
            ->expects($this->once())
            ->method('deleteItem')
            ->with('key');

        $result = $this->middleware->query('foo', $context, fn(): ResultInterface => $expected);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function skips_query_caching_when_not_requested(): void
    {
        $this
            ->cache
            ->expects($this->never())
            ->method($this->anything());

        $expected = new Result(new ArrayDataset('default', []));
        $context  = new QueryContext(
            query: 'foo',
            execution: new Dbal('default'),
        );
        $next     = static fn(): ResultInterface => $expected;
        $result   = $this->middleware->query('foo', $context, $next);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function invalidates_keys(): void
    {
        $this
            ->cache
            ->expects($this->once())
            ->method('deleteItems')
            ->with(['foo', 'bar']);

        $this->middleware->invalidate(new Invalidate(keys: ['foo', 'bar']));
    }

    #[Test]
    public function invalidates_tags(): void
    {
        // We will not use mock from setup.
        $this->cache->expects($this->never())->method($this->anything());
        
        $cache      = $this->createMock(TagAwareAdapterInterface::class);
        $middleware = new CacheMiddleware($cache);

        $cache
            ->expects($this->once())
            ->method('invalidateTags')
            ->with(['foo', 'bar']);

        $middleware->invalidate(new Invalidate(tags: ['foo', 'bar']));
    }

    #[Test]
    public function throws_exception_if_cache_tags_are_not_supported(): void
    {
        $this->expectException(UnsupportedException::class);
        
        // We will not use mock from setup.
        $this->cache->expects($this->never())->method($this->anything());

        $this->middleware->invalidate(new Invalidate(tags: ['foo', 'bar']));
    }
}
