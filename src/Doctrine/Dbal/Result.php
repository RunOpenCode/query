<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use RunOpenCode\Component\Dataset\Collector\ListCollector;
use RunOpenCode\Component\Dataset\Stream;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;

use function RunOpenCode\Component\Query\assert_result_open;

/**
 * Doctrine Dbal result set.
 *
 * @phpstan-import-type Row from DatasetInterface
 *
 * @implements ResultInterface<non-negative-int, Row>
 *
 * @implements \IteratorAggregate<non-negative-int, Row>
 */
final class Result implements \IteratorAggregate, ResultInterface
{
    /**
     * {@inheritdoc}
     */
    public readonly string $connection;

    /**
     * {@inheritdoc}
     */
    public private(set) bool $closed = false;

    /**
     * {@inheritdoc}
     */
    public mixed $upstream {
        get => $this->dataset;
    }

    /**
     * Create new result set from data set retrieved by Doctrine Dbal.
     *
     * @param DatasetInterface $dataset Data set, retrieved by Doctrine Dbal.
     */
    public function __construct(
        private DatasetInterface $dataset,
    ) {
        $this->connection = $this->dataset->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function scalar(bool $nullify = false): int|float|string|object|bool|null
    {
        assert_result_open($this);

        /** @var list<scalar|object> $values */
        $values = Stream::create($this->dataset->vector())
                        ->take(2)
                        ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                        ->collect(ListCollector::class)->value;

        $this->free();

        if (1 === \count($values)) {
            return $values[0];
        }

        if ($nullify) {
            return null;
        }

        throw new NoResultException('Expected one record in result set, none found.');
    }

    /**
     * {@inheritdoc}
     */
    public function vector(bool $nullify = false): ?iterable
    {
        assert_result_open($this);

        $iterator = Stream::create($this->dataset->vector())->getIterator();
        $first    = null;

        foreach ($iterator as $key => $value) {
            $first = static fn(): iterable => yield $key => $value;
            break;
        }

        if (null === $first) {
            $this->free();
            return $nullify ? null : [];
        }

        return Stream::create($first())
                     ->merge($iterator)
                     ->finalize($this->free(...));
    }

    /**
     * {@inheritdoc}
     */
    public function record(bool $nullify = false): object|array|null
    {
        assert_result_open($this);

        $values = Stream::create($this->dataset)
                        ->take(2)
                        ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                        ->collect(ListCollector::class)->value;

        $this->free();

        if (1 === \count($values)) {
            return $values[0]; // @phpstan-ignore-line return.type
        }

        if ($nullify) {
            return null;
        }

        throw new NoResultException('Expected one record in result set, none found.');
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return \iterator_to_array($this);
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        // @phpstan-ignore-next-line
        if (!isset($this->dataset)) {
            return;
        }

        try {
            $this->dataset->free();
        } catch (\Exception) {
            // noop.
        }

        $this->closed = true;

        unset($this->dataset);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        assert_result_open($this);

        try {
            yield from $this->dataset;
        } finally {
            $this->free();
        }
    }

    public function __sleep(): array
    {
        assert_result_open($this);

        $cacheable = new ArrayDataset(
            $this->connection,
            \iterator_to_array($this->dataset),
        );

        $this->dataset->free();

        $this->dataset = $cacheable;

        return ['connection', 'closed', 'dataset'];
    }
}
