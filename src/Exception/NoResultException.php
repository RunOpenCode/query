<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when at least one record is expected in result set, but none retrieved.
 */
class NoResultException extends UnexpectedResultException
{
}
