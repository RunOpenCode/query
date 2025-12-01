<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Configuration;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;

/**
 * Configuration for the execution of query and/or statement by executor adapter.
 *
 * @see AdapterInterface
 */
interface ExecutionInterface
{
    /**
     * Connection name to be used for execution.
     *
     * If null, default connection will be used.
     *
     * @var non-empty-string|null
     */
    public ?string $connection {
        get;
    }

    /**
     * Rule of execution within transaction scope.
     *
     * If provided, overrides {@see ExecutionScope::Strict} rule of execution of query and/or statement within
     * transaction execution scope.
     */
    public ?ExecutionScope $scope {
        get;
    }

    /**
     * Create new instance of this configuration using given connection.
     *
     * @param non-empty-string $connection Connection to use.
     *
     * @return self New instance with provided connection.
     */
    public function withConnection(string $connection): self;
}
