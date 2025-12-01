<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Configuration;

/**
 * Configuration for the execution of the transaction by executor adapter.
 */
interface TransactionInterface
{
    /**
     * Name of the connection for which transactional execution scope should be created.
     *
     * If null, default connection will be used.
     *
     * @var non-empty-string|null
     */
    public ?string $connection {
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
