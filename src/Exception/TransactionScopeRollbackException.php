<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * This exception is being thrown when rolling back transactions within
 * transaction scope failed.
 *
 * This exception can be considered as fatal and should not be mitigated.
 *
 * Everything that could be rolled back is rolled back prior to this exception
 * being thrown.
 */
class TransactionScopeRollbackException extends RollbackTransactionException
{
    /**
     * Collection of all thrown exceptions during rollback attempts.
     *
     * @var non-empty-list<\Throwable>
     */
    public readonly array $trace;

    /**
     * Create instance of distributed transaction rollback exception.
     *
     * @param string     $message  Exception message.
     * @param \Throwable $previous Previous exception which triggered rollback of transaction.
     * @param \Throwable ...$trace Exceptions thrown during attempt to rollback, if rollback failed.
     */
    public function __construct(string $message, \Throwable $previous, \Throwable ...$trace)
    {
        \assert(\count($trace) > 0 && \array_is_list($trace));

        parent::__construct($message, $previous);
        $this->trace = $trace;
    }
}
