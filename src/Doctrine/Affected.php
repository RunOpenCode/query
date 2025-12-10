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
     * Create report about affected rows.
     * 
     * @param non-empty-string $connection Connection which was used to mutate database objects.
     * @param non-negative-int $affected   Number of affected database objects.
     */
    public function __construct(
        public string $connection,
        public int    $affected,
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->affected;
    }
}
