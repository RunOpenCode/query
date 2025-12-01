<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

/**
 * Statement execution result.
 *
 * When statement is executed, result contains total number
 * of affected database objects.
 *
 * Optionally, concrete implementation may extend upon that
 * and provide report in details (number of updated, deleted,
 * inserted objects, etc.)
 */
interface AffectedInterface extends \Countable
{
}
