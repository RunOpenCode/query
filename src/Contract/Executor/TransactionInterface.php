<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Configuration of the transactional scope.
 */
interface TransactionInterface
{
    /**
     * Name of the connection for which transactional scope should be created.
     *
     * @var non-empty-string
     */
    public string $connection {
        get;
    }
}
