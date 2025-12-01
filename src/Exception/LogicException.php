<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Library's logic exception.
 *
 * Denotes semantical errors in code or configuration and should not be handled.
 */
class LogicException extends \LogicException implements ExceptionInterface
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
