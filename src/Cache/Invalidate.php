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
     * @var list<non-empty-string> List of cache keys to invalidate.
     */
    public array $keys;

    /**
     * @var list<string> List of cache tags to invalidate.
     */
    public array $tags;

    /**
     * Create cache invalidation command (event).
     *
     * @param non-empty-string|non-empty-string[]|null $keys Keys to invalidate.
     * @param non-empty-string|non-empty-string[]|null $tags Tags to invalidate.
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

    /**
     * Create cache invalidation command for keys.
     *
     * @param non-empty-string|non-empty-string[] $keys Keys to invalidate
     */
    public static function keys(string|array $keys): self
    {
        return new self(keys: $keys);
    }

    /**
     * Create cache invalidation command for tags.
     *
     * @param non-empty-string|non-empty-string[] $tags Tags to invalidate
     */
    public static function tags(string|array $tags): self
    {
        return new self(tags: $tags);
    }
}
