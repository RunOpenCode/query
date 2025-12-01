<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when adapter could not modify transaction isolation level.
 */
class IsolationException extends TransactionException
{
}
