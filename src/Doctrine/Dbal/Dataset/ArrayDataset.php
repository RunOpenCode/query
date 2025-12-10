<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal\Dataset;

use RunOpenCode\Component\Query\Doctrine\Dbal\DatasetInterface;

/**
 * In memory dataset, suitable for serialization.
 *
 * @phpstan-import-type Row from DatasetInterface
 */
final class ArrayDataset implements DatasetInterface
{
    /**
     * Create new array result set.
     *
     * @param non-empty-string $connection Connection which was used to produce result set.
     * @param list<Row>        $result     Result set.
     */
    public function __construct(
        public readonly string $connection,
        private array          $result
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function vector(): iterable
    {
        foreach ($this->result as $row) {
            yield \array_values($row)[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        unset($this->result);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from $this->result;
    }
}
