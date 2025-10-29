<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Cache;

use Psr\Cache\CacheItemInterface;

/**
 * Cache identity.
 *
 * Cache identity defines cache key and a resolver callable
 * that will be used to determine cache tags and TTL.
 *
 * Identity is used in caching middleware to identify cache item
 * for execution result.
 *
 * Cache resolver callable will be invoked with two arguments:
 *
 * - CacheItemInterface $item - cache item that will be stored,
 * - CacheableResultInterface $result - result that is intended to be cached.
 *
 * You may use these arguments to set tags and TTL on the cache item.
 *
 * If you wish to prevent caching of the result, you may return FALSE
 * from the resolver callable.
 *
 * @phpstan-type CacheResolverCallable = \Closure(CacheItemInterface, CacheableResultInterface=): (false|void)
 */
interface CacheIdentityInterface
{
    /**
     * Get cache key.
     */
    public string $key {
        get;
    }

    /**
     * A callable that will resolve cache tags
     * and TTL when invoked.
     *
     * Resolver may return FALSE to indicate that
     * the result should not be cached.
     *
     * @var CacheResolverCallable
     */
    public \Closure $resolver {
        get;
    }
}
