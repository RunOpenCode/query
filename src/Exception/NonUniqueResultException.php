<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when one record is expected in result set, but multiple retrieved.
 */
class NonUniqueResultException extends UnexpectedResultException
{
}
