<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use RunOpenCode\Component\Dataset\Collector\ListCollector;
use RunOpenCode\Component\Dataset\Reducer\Callback;
use RunOpenCode\Component\Dataset\Stream;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;

use function RunOpenCode\Component\Query\assert_default_value;
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
    public function scalar(mixed ...$default): mixed
    {
        assert_result_open($this);
        assert_default_value(...$default);

        try {
            return Stream::create($this->dataset->vector())
                         ->take(2)
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->ifEmpty(new NoResultException('Expected one record in result set, none found.'))
                         ->reduce(Callback::class, static fn(mixed $carry, mixed $value): mixed => $value, null);
        } catch (NoResultException $exception) {
            return \array_key_exists(0, $default) ? $default[0] : throw $exception;
        } finally {
            $this->free();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function vector(mixed ...$default): mixed
    {
        assert_result_open($this);
        assert_default_value(...$default);

        try {
            $value = Stream::create($this->dataset->vector())->collect(ListCollector::class)->value;

            return 0 === \count($value) && \array_key_exists(0, $default) ? $default[0] : $value; // @phpstan-ignore-line
        } finally {
            $this->free();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function record(mixed ...$default): mixed
    {
        assert_result_open($this);
        assert_default_value(...$default);

        try {
            return Stream::create($this->dataset)
                         ->take(2)
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->ifEmpty(new NoResultException('Expected one record in result set, none found.'))
                         ->collect(ListCollector::class)[0];
        } catch (NoResultException $exception) {
            return \array_key_exists(0, $default) ? $default[0] : throw $exception;
        } finally {
            $this->free();
        }
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
