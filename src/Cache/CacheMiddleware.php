<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Cache;

use Psr\Cache\CacheItemPoolInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentifiableInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentityInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Context\ContextInterface;
use RunOpenCode\Component\Query\Contract\Context\QueryContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\QueryMiddlewareInterface;
use RunOpenCode\Component\Query\Contract\Context\TransactionContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\TransactionMiddlewareInterface;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\UnsupportedException;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * Caching middleware.
 *
 * It provides caching capabilities for query results based on PSR-6 cache pool. It also expose a method
 * to invalidate cache items based on keys or tags.
 *
 * Cache middleware will require from context to provide either {@see CacheIdentityInterface} or
 * {@see CacheIdentifiableInterface} in order to perform caching. However, if no cache pool is provided
 * caching will be effectively disabled ({@see NullAdapter} will be used).
 *
 * Note that cache key and cache tags are not sanitized, underlying adapter may throw exception if you use
 * reserved character in keys/tags.
 *
 * Caching for statements is not supported.
 *
 * @phpstan-import-type Next from QueryMiddlewareInterface as NextQuery
 * @phpstan-import-type Next from TransactionMiddlewareInterface as NextTransaction
 * @phpstan-import-type TransactionalFn from TransactionMiddlewareInterface
 */
final readonly class CacheMiddleware implements QueryMiddlewareInterface, TransactionMiddlewareInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(
        ?CacheItemPoolInterface $cache = null,
    ) {
        $this->cache = $cache ?? new TagAwareAdapter(new NullAdapter());
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, QueryContextInterface $context, callable $next): ResultInterface
    {
        return $this->cached($query, $context, $next);
    }

    /**
     * {@inheritdoc}
     */
    public function transactional(callable $function, TransactionContextInterface $context, callable $next): mixed
    {
        return $this->cached($function, $context, $next);
    }

    /**
     * Invalidate cache items based on provided invalidate instruction.
     *
     * If no cache pool is provided, this method will be a noop.
     *
     * If tags are provided for invalidation, but cache pool does not support
     * tag invalidation, a {@see LogicException} will be thrown.
     *
     * @param Invalidate $invalidate Invalidate instruction.
     *
     * @throws LogicException If cache pool does not support tag invalidation (implementation of {@see CacheItemPoolInterface} does not have method `invalidateTags()`).
     */
    public function invalidate(Invalidate $invalidate): void
    {
        if ($invalidate->keys !== []) {
            $this->cache->deleteItems($invalidate->keys);
        }

        if ($invalidate->tags === []) {
            return;
        }

        if (!\method_exists($this->cache, 'invalidateTags')) {
            throw new UnsupportedException('Cache pool does not support tag invalidation.');
        }

        $this->cache->invalidateTags($invalidate->tags);
    }

    /**
     * Cache query result or transaction result.
     *
     * Method delivers results from the cache, or passes execution to
     * next middleware and caches the result for next invocations.
     *
     * @param non-empty-string|TransactionalFn                  $subject Query or transactional function.
     * @param QueryContextInterface|TransactionContextInterface $context Current context.
     * @param NextQuery|NextTransaction                         $next    Next middleware to call.
     *
     * @return ($subject is non-empty-string ? ResultInterface : mixed)
     */
    private function cached(callable|string $subject, ContextInterface $context, callable $next): mixed
    {
        $identity = $context->require(CacheIdentityInterface::class) ?? $context->require(CacheIdentifiableInterface::class);

        if (null === $identity) {
            return $next($subject, $context);
        }

        $identity = $identity instanceof CacheIdentifiableInterface ? $identity->getCacheIdentity() : $identity;
        $item     = $this->cache->getItem($identity->key);

        if ($item->isHit()) {
            return $item->get();
        }

        $result = $next($subject, $context);

        if (false !== ($identity->resolver)($item, $result)) {
            $this->cache->save($item);
            return $result;
        }

        // release cache item if caching is not desired
        $this->cache->deleteItem($identity->key);

        return $result;
    }
}
