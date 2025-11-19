<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Replica;

enum FallbackStrategy
{
    /**
     * Do not use fallback connection.
     */
    case None;

    /**
     * Use replica connections and primary as fallback.
     */
    case Any;

    /**
     * Use primary connection as fallback.
     */
    case Primary;

    /**
     * Use any replica connection.
     */
    case Replicas;
}
