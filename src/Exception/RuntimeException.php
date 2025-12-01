<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * General library's runtime exception.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
