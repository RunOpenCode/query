<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;

/**
 * Thrown when adapter not execute the transaction.
 *
 * It is a base class for specific transaction exceptions.
 */
class TransactionException extends RuntimeException implements AdapterAwareExceptionInterface
{
    public function __construct(
        string                            $message,
        ?\Throwable                       $previous = null,
        public readonly ?AdapterInterface $adapter = null,
    ) {
        parent::__construct($message, $previous);
    }
}
