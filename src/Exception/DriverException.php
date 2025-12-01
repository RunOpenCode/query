<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

use RunOpenCode\Component\Query\Contract\Executor\AdapterInterface;

/**
 * Thrown when adapter throws exception for which this library does not have a wrapper exception.
 */
class DriverException extends RuntimeException implements AdapterAwareExceptionInterface
{
    public function __construct(
        string                            $message,
        ?\Throwable                       $previous = null,
        public readonly ?AdapterInterface $adapter = null,
    ) {
        parent::__construct($message, $previous);
    }
}
