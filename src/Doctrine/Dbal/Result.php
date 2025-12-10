<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use RunOpenCode\Component\Dataset\Collector\ListCollector;
use RunOpenCode\Component\Dataset\Reducer\Callback;
use RunOpenCode\Component\Dataset\Stream;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\ResultClosedException;

/**
 * Doctrine Dbal result set.
 *
 * @phpstan-import-type Row from DatasetInterface
 *
 * @implements ResultInterface<non-negative-int, Row>
 *
 * @implements \IteratorAggregate<Row>
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
    public private(set) bool $closed;

    /**
     * Create new result set from data set retrieved by Doctrine Dbal.
     *
     * @param DatasetInterface $dataset Data set, retrieved by Doctrine Dbal.
     */
    public function __construct(
        private DatasetInterface $dataset,
    ) {
        $this->connection = $this->dataset->connection;
        $this->closed     = false;
    }

    /**
     * {@inheritdoc}
     */
    public function scalar(mixed ...$default): mixed
    {
        $this->assertNotClosed();
        $this->assertDefaultValue(...$default);

        try {
            return Stream::create($this->dataset->vector())
                         ->take(2)
                         ->ifEmpty(static fn(): iterable => \array_key_exists(0, $default) ? [$default[0]] : throw new NoResultException('Expected one record in result set, none found.'))
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->reduce(Callback::class, static fn(mixed $carry, mixed $value): mixed => $value, null);
        } finally {
            $this->free();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function vector(mixed ...$default): mixed
    {
        $this->assertNotClosed();
        $this->assertDefaultValue(...$default);

        try {
            $value = Stream::create($this->dataset->vector())->collect(ListCollector::class)->value;
            
            return 0 === \count($value) &&  \array_key_exists(0, $default) ? $default[0] : $value;
        } finally {
            $this->free();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function record(mixed ...$default): mixed
    {
        $this->assertNotClosed();
        $this->assertDefaultValue(...$default);

        try {
            return Stream::create($this->dataset)
                         ->take(2)
                         ->ifEmpty(static fn(): iterable => \array_key_exists(0, $default) ? [$default[0]] : throw new NoResultException('Expected one record in result set, none found.'))
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->collect(ListCollector::class)[0];
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
        $this->assertNotClosed();

        try {
            yield from $this->dataset;
        } finally {
            $this->free();
        }
    }

    public function __sleep(): array
    {
        $this->assertNotClosed();

        $cacheable = new ArrayDataset(
            $this->connection,
            \iterator_to_array($this->dataset),
        );

        $this->dataset->free();

        $this->dataset = $cacheable;

        return ['connection', 'closed', 'dataset'];
    }

    /**
     * Assert that result set is not closed.
     */
    private function assertNotClosed(): void
    {
        if (!$this->closed) {
            return;
        }

        throw new ResultClosedException(\sprintf(
            'Can not call method "%s" on closed result set.',
            \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function']
        ));
    }

    /**
     * Assert that at most one default value is provided.
     */
    private function assertDefaultValue(mixed ...$default): void
    {
        if (\count($default) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Expected at most one default value when invoking method "%s" of result set, %d given.',
                \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'],
                \count($default),
            ));
        }

        if (!\array_is_list($default)) {
            throw new InvalidArgumentException(\sprintf(
                'Expected default value to be provided without naming argument when invoking method "%s" of result set, "%s" given.',
                \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS)[1]['function'],
                \array_keys($default)[0],
            ));
        }
    }
}
