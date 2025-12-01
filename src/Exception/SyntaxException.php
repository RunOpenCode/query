<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when syntax error is detected either by query parser or by underlying database.
 *
 * This exception should not be handled, query should be fixed.
 */
class SyntaxException extends RuntimeException
{
}
