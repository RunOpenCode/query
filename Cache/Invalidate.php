<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Cache;

/**
 * Cache invalidation command (event).
 * 
 * This event is captured by caching middleware to invalidate
 * specific cache keys and/or tags.
 */
final readonly class Invalidate
{
    /**
     * @var list<string> List of cache keys to invalidate.
     */
    public array $keys;

    /**
     * @var list<string> List of cache tags to invalidate.
     */
    public array $tags;

    /**
     * Create cache invalidation command (event).
     *
     * @param string|string[]|null $keys Keys to invalidate.
     * @param string|string[]|null $tags Tags to invalidate.
     */
    public function __construct(
        string|array|null $keys = null,
        string|array|null $tags = null,
    ) {
        $keys = $keys ?? [];
        $tags = $tags ?? [];

        $this->keys = \array_values(\is_string($keys) ? [$keys] : $keys);
        $this->tags = \array_values(\is_string($tags) ? [$tags] : $tags);
    }
}