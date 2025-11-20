<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Replica;

enum FallbackStrategy: string
{
    /**
     * Do not use fallback connection.
     */
    case None = 'none';

    /**
     * Use replica connections and primary as fallback.
     */
    case Any = 'any';

    /**
     * Use primary connection as fallback.
     */
    case Primary = 'primary';

    /**
     * Use any replica connection.
     */
    case Replicas = 'replicas';
}