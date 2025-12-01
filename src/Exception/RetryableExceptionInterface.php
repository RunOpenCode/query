<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Marker interface for all exceptions where retrying failed transaction makes sense.
 */
interface RetryableExceptionInterface extends ExceptionInterface
{
}
