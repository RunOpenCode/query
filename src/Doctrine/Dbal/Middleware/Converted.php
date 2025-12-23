<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal\Middleware;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RunOpenCode\Component\Dataset\Collector\ListCollector;
use RunOpenCode\Component\Dataset\Reducer\Callback;
use RunOpenCode\Component\Dataset\Stream;
use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Dataset\ArrayDataset;
use RunOpenCode\Component\Query\Doctrine\Dbal\DatasetInterface;
use RunOpenCode\Component\Query\Doctrine\Dbal\Result;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\ResultClosedException;

use function RunOpenCode\Component\Query\assert_default_value;

/**
 * Result set with converted column values according to provided conversion functions.
 *
 * @phpstan-import-type Row from DatasetInterface
 *
 * @implements ResultInterface<non-negative-int, mixed>
 * @implements \IteratorAggregate<non-negative-int, mixed>
 */
final class Converted implements \IteratorAggregate, ResultInterface
{
    /**
     * {@inheritdoc}
     */
    public readonly string $connection;

    /**
     * {@inheritdoc}
     */
    public bool $closed {
        get => $this->result->closed;
    }

    /**
     * Create result set with converted values.
     *
     * @param ResultInterface<non-negative-int, Row> $result        Result to convert values.
     * @param Convert                                $configuration Conversion configuration.
     * @param AbstractPlatform                       $platform      Dbal platform.
     */
    public function __construct(
        private ResultInterface  $result,
        private Convert          $configuration,
        private AbstractPlatform $platform,
    ) {
        $this->connection = $this->result->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function scalar(...$default): mixed
    {
        assert_default_value(...$default);

        try {
            return Stream::create($this->result)
                         ->take(2)
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->map(static fn(array $row): array => [\array_key_first($row) => array_first($row)])
                         ->map($this->convert(...)) // @phpstan-ignore-line
                         ->ifEmpty(new NoResultException('Expected one record in result set, none found.'))
                         ->reduce(Callback::class, static fn(mixed $carry, array $value): mixed => \array_values($value)[0], null);
        } catch (NoResultException $exception) {
            return \array_key_exists(0, $default) ? $default[0] : throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function vector(...$default): mixed
    {
        assert_default_value(...$default);

        $value = Stream::create($this->result)
                       ->map(static fn(array $row): array => [\array_key_first($row) => array_first($row)])
                       ->map($this->convert(...)) // @phpstan-ignore-line
                       ->map(static fn(array $row): mixed => array_first($row))
                       ->collect(ListCollector::class)->value;

        return 0 === \count($value) && \array_key_exists(0, $default) ? $default[0] : $value; // @phpstan-ignore-line
    }

    /**
     * {@inheritdoc}
     */
    public function record(...$default): mixed
    {
        assert_default_value(...$default);

        try {
            return Stream::create($this->result)
                         ->take(2)
                         ->overflow(1, new NonUniqueResultException('Expected only one record in result set, multiple retrieved.'))
                         ->map($this->convert(...))
                         ->ifEmpty(new NoResultException('Expected one record in result set, none found.'))
                         ->collect(ListCollector::class)[0];
        } catch (NoResultException $exception) {
            return \array_key_exists(0, $default) ? $default[0] : throw $exception;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return \iterator_to_array($this->result);
    }

    /**
     * {@inheritdoc}
     */
    public function free(): void
    {
        // @phpstan-ignore-next-line
        if (!isset($this->result)) {
            return;
        }

        try {
            $this->result->free();
        } catch (\Exception) {
            // noop.
        }

        // Kill references to help garbage collection.
        unset($this->configuration, $this->platform);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        yield from Stream::create($this->result)
                         ->map($this->convert(...));
    }

    public function __sleep(): array
    {
        if ($this->closed) {
            throw new ResultClosedException('Can not call method "__sleep()" on closed result set.');
        }

        $cacheable = new Result(new ArrayDataset(
            $this->connection,
            \iterator_to_array($this->result),
        ));

        $this->result->free();

        $this->result = $cacheable;

        unset($this->configuration, $this->platform);

        return ['connection', 'result'];
    }

    /**
     * Convert row according to the conversion configuration.
     *
     * @param Row $row Row to convert values.
     *
     * @return array<non-empty-string, mixed> Row with converted values.
     */
    private function convert(array $row): array
    {
        // Already converted.
        // @phpstan-ignore-next-line
        if (!isset($this->configuration)) {
            return $row;
        }

        foreach ($row as $column => &$value) {
            if (!$this->configuration->has($column)) {
                continue;
            }

            $converter = $this->configuration->get($column);

            if (\is_callable($converter)) {
                $value = $converter($value, $this->platform);
                continue;
            }

            $value = Type::getType($converter)->convertToPHPValue($value, $this->platform);
        }

        return $row;
    }
}
