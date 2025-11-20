<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Replica;

/**
 * Use database replica for executing query.
 */
final readonly class Replica
{
    /**
     * Create replica configuration.
     *
     * @param non-empty-string|null $connection Connection for which replica should be used for query (NULL denotes default connection).
     * @param FallbackStrategy|null $fallback   Fallback configuration, or null, if default strategy should be used.
     */
    public function __construct(
        public ?string           $connection = null,
        public ?FallbackStrategy $fallback = null,
    ) {
        // noop.
    }
}
