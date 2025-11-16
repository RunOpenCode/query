<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Replica;

/**
 * Use database replica for executing query.
 */
final readonly class Replica
{
    /**
     * Create replica configuration.
     *
     * @param non-empty-string|null $connection Connection for which replica should be used for query (NULL denotes default connection).
     * @param FallbackStrategy      $fallback   Fallback configuration.
     */
    public function __construct(
        public ?string          $connection = null,
        public FallbackStrategy $fallback = FallbackStrategy::Primary,
    ) {
        // noop.
    }
}
