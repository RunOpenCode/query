<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Cache;

use Psr\Cache\CacheItemInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentityInterface;
use RunOpenCode\Component\Query\Exception\UnsupportedException;

/**
 * {@inheritdoc}
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
     * Create new dynamic cache identity.
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
     * Instead of a dynamic resolver, this method will create
     * a resolver that will always set the provided tags and TTL.
     *
     * @param string        $key  Cache key.
     * @param string[]|null $tags Cache tags.
     * @param int|null      $ttl  Cache time to live in seconds.
     */
    public static function static(string $key, ?array $tags = [], ?int $ttl = null): self
    {
        return new self($key, static function(CacheItemInterface $item) use ($tags, $ttl): void {
            $item->expiresAfter($ttl);

            if (empty($tags)) {
                return;
            }

            if (method_exists($item, 'tag')) {
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
