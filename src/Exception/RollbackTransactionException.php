<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when adapter could not rollback the transaction.
 */
class RollbackTransactionException extends TransactionException
{
}
