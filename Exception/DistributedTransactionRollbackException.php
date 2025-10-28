<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * This exception is being thrown in distributed transaction scope when
 * one or multiple adapters in transaction failed to rollback.
 *
 * This exception can be considered as fatal and should not be mitigated.
 */
class DistributedTransactionRollbackException extends RollbackTransactionException
{
    /**
     * This exception is be
     *
     * @var \Exception[]
     */
    public readonly array $rollbackExceptions;

    /**
     * Create instance of distributed transaction rollback exception.
     *
     * @param string       $message            Exception message.
     * @param \Exception   $previous           Previous exception which triggered rollback of transaction.
     * @param \Exception[] $rollbackExceptions Exception thrown during attempt to rollback.
     */
    public function __construct(string $message, \Exception $previous, array $rollbackExceptions = [])
    {
        parent::__construct($message, $previous);
        $this->rollbackExceptions = $rollbackExceptions;
    }
}