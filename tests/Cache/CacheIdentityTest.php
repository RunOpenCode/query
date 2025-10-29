<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Tests\Cache;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use RunOpenCode\Component\Query\Cache\CacheIdentity;
use RunOpenCode\Component\Query\Exception\UnsupportedException;
use Symfony\Contracts\Cache\ItemInterface;

final class CacheIdentityTest extends TestCase
{
    #[Test]
    public function creates_static_identity(): void
    {
        $identity = CacheIdentity::static('foo', ['bar', 'baz'], 1000);
        $item     = $this->createMock(ItemInterface::class);

        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(1000);

        $item
            ->expects($this->once())
            ->method('tag')
            ->with(['bar', 'baz']);

        ($identity->resolver)($item);

        $this->assertSame('foo', $identity->key);
    }

    #[Test]
    public function throws_exception_when_tagging_is_not_supported(): void
    {
        $this->expectException(UnsupportedException::class);

        $identity = CacheIdentity::static('foo', ['bar', 'baz'], 1000);
        $item     = $this->createMock(CacheItemInterface::class);

        $item
            ->expects($this->once())
            ->method('expiresAfter')
            ->with(1000);

        ($identity->resolver)($item);
    }
}
