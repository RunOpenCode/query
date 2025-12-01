<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine;

use RunOpenCode\Component\Query\Contract\Executor\AffectedInterface;

/**
 * Doctrine reports only about affected rows.
 */
final readonly class Affected implements AffectedInterface
{
    /**
     * @param non-negative-int $affected Number of affected database objects.
     */
    public function __construct(public int $affected)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->affected;
    }
}
