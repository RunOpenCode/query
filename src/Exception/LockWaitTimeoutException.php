<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;

/**
 * Thrown when adapter stumbled upon lock wait timeout.
 *
 * Transaction in that case may be restarted.
 */
class LockWaitTimeoutException extends RuntimeException implements RetryableExceptionInterface, AdapterAwareExceptionInterface
{
    public function __construct(
        string                            $message,
        ?\Throwable                       $previous = null,
        public readonly ?AdapterInterface $adapter = null,
    ) {
        parent::__construct($message, $previous);
    }
}
