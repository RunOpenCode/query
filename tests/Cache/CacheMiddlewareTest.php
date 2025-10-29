<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Cache;

use Doctrine\DBAL\Cache\ArrayResult;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use RunOpenCode\Component\Query\Cache\CacheIdentity;
use RunOpenCode\Component\Query\Cache\CacheMiddleware;
use RunOpenCode\Component\Query\Cache\Invalidate;
use RunOpenCode\Component\Query\Contract\Cache\CacheableResultInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentifiableInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentityInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\UnsupportedException;
use RunOpenCode\Component\Query\Middleware\Context;
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
    public function caches(): void
    {
        $expected = $this->createMock(CacheableResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new Context(configuration: [
            CacheIdentity::static('key'),
        ]);

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

        $result = $this->middleware->query('foo', $context, fn(): CacheableResultInterface => $expected);

        $this->assertSame($expected, $result);
    }

    #[Test]
    public function provides_cached_result(): void
    {
        $expected = $this->createMock(ResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new Context(configuration: [
            CacheIdentity::static('key'),
        ]);

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
    public function throws_exception_when_caching_non_cacheable_result(): void
    {
        $this->expectException(LogicException::class);

        $item    = $this->createMock(ItemInterface::class);
        $context = new Context(configuration: [
            CacheIdentity::static('key'),
        ]);

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

        // Result that does not implement CacheableResultInterface
        $this->middleware->query('foo', $context, fn(): ResultInterface => $this->createMock(ResultInterface::class));
    }

    #[Test]
    public function skips_caching_when_instructed(): void
    {
        $expected = $this->createMock(CacheableResultInterface::class);
        $item     = $this->createMock(ItemInterface::class);
        $context  = new Context(configuration: [
            new CacheIdentity('key', static fn(): false => false),
        ]);

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

        $result = $this->middleware->query('foo', $context, fn(): CacheableResultInterface => $expected);

        $this->assertSame($expected, $result);
    }

    #[Test]
    #[DataProvider('get_data_for_test_skips_caching_when_not_requested')]
    public function skips_caching_when_not_requested(string $method, ResultInterface|int $expected): void
    {
        $this
            ->cache
            ->expects($this->never())
            ->method($this->anything());

        $context = new Context();
        $next    = static fn(): ResultInterface|int => $expected;
        $result  = $this->middleware->{$method}('foo', $context, $next);

        $this->assertSame($expected, $result);
    }

    /**
     * @return iterable<string, array{non-empty-string, ResultInterface|int}>
     */
    public static function get_data_for_test_skips_caching_when_not_requested(): iterable
    {
        yield 'Method `query`.' => ['query', new Result(new ArrayResult([], []))];
        yield 'Method `statement`.' => ['statement', 42];
    }

    /**
     * @param class-string $class
     */
    #[Test]
    #[DataProvider('get_data_for_statement_forbids_caching')]
    public function statement_forbids_caching(string $class): void
    {
        $this->expectException(LogicException::class);

        $context = new Context(configuration: [
            $this->createMock($class),
        ]);

        $this
            ->cache
            ->expects($this->never())
            ->method($this->anything());

        $this->middleware->statement('foo', $context, fn(): never => $this->fail('Middleware chain should be interrupted.'));
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function get_data_for_statement_forbids_caching(): iterable
    {
        yield \sprintf('Requested with "%s".', CacheIdentityInterface::class) => [CacheIdentityInterface::class];
        yield \sprintf('Requested with "%s".', CacheIdentifiableInterface::class) => [CacheIdentifiableInterface::class];
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

        $this->middleware->invalidate(new Invalidate(tags: ['foo', 'bar']));
    }
}
