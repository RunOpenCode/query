<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Monitor;

/**
 * Configure monitoring of slow queries/statements.
 */
final readonly class Slow
{
    /**
     * Configure monitoring of slow query/statement.
     *
     * @param positive-int     $threshold The duration in milliseconds beyond which the execution is deemed ‘slow.’ If
     *                                    not specified, the default value applies.
     * @param non-empty-string $identity  Name which should be used to identify execution. For queries and statements
     *                                    this value is optional, if not provided, executed query will be used. For
     *                                    transactions this value is required.
     */
    public function __construct(
        public ?int    $threshold = null,
        public ?string $identity = null,
    ) {
        // noop
    }
}
