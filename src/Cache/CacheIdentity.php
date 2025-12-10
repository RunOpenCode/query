<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Cache;

use Psr\Cache\CacheItemInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentityInterface;
use RunOpenCode\Component\Query\Exception\UnsupportedException;

/**
 * This is default implementation of {@see CacheIdentityInterface}.
 *
 * @phpstan-import-type CacheResolverCallable from CacheIdentityInterface
 */
final readonly class CacheIdentity implements CacheIdentityInterface
{
    /**
     * @var CacheResolverCallable
     */
    public \Closure $resolver;

    /**
     * Create new cache identity.
     *
     * @param string                $key      Cache key.
     * @param CacheResolverCallable $resolver A callable that will resolve cache tags and TTL when invoked.
     */
    public function __construct(
        public string $key,
        callable      $resolver,
    ) {
        $this->resolver = $resolver(...);
    }

    /**
     * Create new static cache identity.
     *
     * Instead of providing resolver, this method will create
     * a resolver that will always set the provided tags and TTL
     * with given values.
     *
     * @param string        $key  Cache key.
     * @param string[]|null $tags Cache tags.
     * @param int|null      $ttl  Cache time to live in seconds.
     *
     * @throws UnsupportedException If cache implementation does not supports tagging (missing `tag` method at concrete implementation of {@see CacheItemInterface}).
     */
    public static function static(string $key, ?array $tags = [], ?int $ttl = null): self
    {
        return new self($key, static function(CacheItemInterface $item) use ($tags, $ttl): void {
            $item->expiresAfter($ttl);

            if ($tags === null || $tags === []) {
                return;
            }

            if (\method_exists($item, 'tag')) {
                $item->tag($tags);
                return;
            }

            throw new UnsupportedException(\sprintf(
                'Cache item of class "%s" does not support tagging. Cannot assign tags "%s" to cache key "%s".',
                \get_class($item),
                \implode('", "', $tags),
                $item->getKey(),
            ));
        });
    }
}
