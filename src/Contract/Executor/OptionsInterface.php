<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Value object which configures execution of query or statement of executor adapter.
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
     * If provided, overrides {@see ExecutionScope::Strict} rule of execution of
     * queries/statements within transaction execution scope.
     */
    public ?ExecutionScope $scope {
        get;
    }

    /**
     * Create new instance of this configuration using different connection.
     * 
     * @param non-empty-string $connection Connection to use.
     *
     * @return self New instance with modified connection.
     */
    public function withConnection(string $connection): self;
}
