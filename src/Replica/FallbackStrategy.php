<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Replica;

/**
 * Fallback strategy for replica.
 */
enum FallbackStrategy: string
{
    /**
     * Do not use fallback connection.
     */
    case None = 'none';

    /**
     * Use all replica connections first and than primary connection as last fallback.
     */
    case Any = 'any';

    /**
     * Use primary connection as fallback.
     */
    case Primary = 'primary';

    /**
     * Use any replica connection, but don't use primary connection.
     */
    case Replicas = 'replicas';
}
