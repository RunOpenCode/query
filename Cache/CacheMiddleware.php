<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Cache;

use Psr\Cache\CacheItemPoolInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheableResultInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentifiableInterface;
use RunOpenCode\Component\Query\Contract\Cache\CacheIdentityInterface;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Contract\Middleware\ContextInterface;
use RunOpenCode\Component\Query\Contract\Middleware\MiddlewareInterface;
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
 * Cache middleware will require from context  to provide either `CacheIdentityInterface` or
 * `CacheIdentifiableInterface` in order to perform caching. However, if no cache pool is provided
 * caching will be effectively disabled.
 *
 * Some runtime checks are performed to ensure valid caching:
 *
 * - If no cache identity is provided in context, caching is skipped.
 * - If the result does not implement `CacheableResultInterface`, a `LogicException` is thrown.
 * - If caching is requested for statement execution, a `LogicException` is thrown.
 * - If cache pool does not support tag invalidation, a `UnsupportedException` is thrown when attempting
 *   to invalidate by tags.
 * 
 * @internal
 */
final readonly class CacheMiddleware implements MiddlewareInterface
{
    private CacheItemPoolInterface $cache;

    public function __construct(
        ?CacheItemPoolInterface $cache = null
    ) {
        $this->cache = $cache ?? new TagAwareAdapter(new NullAdapter());
    }

    /**
     * {@inheritdoc}
     */
    public function query(string $query, ContextInterface $context, callable $next): ResultInterface
    {
        $identity = $context->require(CacheIdentityInterface::class) ?? $context->require(CacheIdentifiableInterface::class);

        if (null === $identity) {
            return $next($query, $context);
        }

        $identity = $identity instanceof CacheIdentifiableInterface ? $identity->getCacheIdentity() : $identity;
        $item     = $this->cache->getItem($identity->key);

        if ($item->isHit()) {
            /** @var ResultInterface $result */
            $result = $item->get();

            return $result;
        }

        $result = $next($query, $context);

        if (!$result instanceof CacheableResultInterface) {
            throw new LogicException(\sprintf(
                'Provided result implementation "%s" does not implements "%s" and therefore it is not cacheable.',
                $result::class,
                CacheableResultInterface::class
            ));
        }

        if (false !== ($identity->resolver)($item, $result)) {
            $this->cache->save($item);
            return $result;
        }

        // release cache item if caching is not desired
        $this->cache->deleteItem($identity->key);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function statement(string $query, ContextInterface $context, callable $next): int
    {
        $identity = $context->require(CacheIdentityInterface::class) ?? $context->require(CacheIdentifiableInterface::class);

        if (null === $identity) {
            return $next($query, $context);
        }

        throw new LogicException('Caching must not be requested for statement execution.');
    }

    /**
     * Invalidate cache items based on provided invalidate instruction.
     *
     * If no cache pool is provided, this method will be a noop.
     *
     * If tags are provided for invalidation, but cache pool does not support
     * tag invalidation, a `LogicException` will be thrown.
     *
     * @param Invalidate $invalidate Invalidate instruction.
     *
     * @throws LogicException If cache pool does not support tag invalidation.
     */
    public function invalidate(Invalidate $invalidate): void
    {
        if (!empty($invalidate->keys)) {
            $this->cache->deleteItems($invalidate->keys);
        }

        if (empty($invalidate->tags)) {
            return;
        }

        if (!\method_exists($this->cache, 'invalidateTags')) {
            throw new UnsupportedException('Cache pool does not support tag invalidation.');
        }

        $this->cache->invalidateTags($invalidate->tags);
    }
}