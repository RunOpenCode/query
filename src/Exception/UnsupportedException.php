<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Exception;

/**
 * Thrown when an unsupported operation is attempted.
 *
 * Lack of support may be due to various reasons, such as:
 *
 * - The feature is not implemented in the current version.
 * - The underlying system or library does not provide support for the requested operation.
 * - The operation is not applicable in the current context or configuration.
 */
class UnsupportedException extends RuntimeException
{
}
