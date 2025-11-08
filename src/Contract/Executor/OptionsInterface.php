<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Value object which configures execution of query and/or statement of executor adapter.
 */
interface OptionsInterface
{
    /**
     * Connection name to be used for query execution,
     * or null if default connection should be used.
     *
     * Connection name is executor specific.
     *
     * @var non-empty-string|null
     */
    public ?string $connection {
        get;
    }

    /**
     * Tags associated with the query execution, or null if none.
     *
     * @var non-empty-string[]|null
     */
    public ?array $tags {
        get;
    }

    /**
     * If provided, overrides {@see ExecutionScope::Strict} rule of execution of
     * queries/statements within transaction execution scope.
     */
    public ?ExecutionScope $scope {
        get;
    }

    /**
     * Checks if query/statement execution is tagged with given tag.
     *
     * @param non-empty-string $tag Tag to search for.
     *
     * @return bool True if tag is present, false otherwise.
     */
    public function has(string $tag): bool;
}
