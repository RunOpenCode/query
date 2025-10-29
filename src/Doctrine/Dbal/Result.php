<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use Doctrine\DBAL\Cache\ArrayResult;
use Doctrine\DBAL\Driver\Exception as DbalDriverException;
use Doctrine\DBAL\Driver\Result as DbalDriverResult;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\DBAL\Result as DbalResult;
use RunOpenCode\Component\Query\Contract\Cache\CacheableResultInterface;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\LogicException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Wrapper for Doctrine Dbal result.
 *
 * This class wraps Dbal resultset and provides you with useful
 * methods to work with dataset retrieved with SELECT statement.
 *
 * @implements \IteratorAggregate<array-key, mixed>
 */
final class Result implements \IteratorAggregate, DbalDriverResult, CacheableResultInterface
{
    public function __construct(
        private DbalDriverResult|DbalResult $result
    ) {
        // noop.
    }

    /**
     * {@inheritdoc}
     */
    public function getScalar(mixed ...$default): mixed
    {
        $this->assertResultAvailable(__METHOD__);

        if (\count($default) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Expected at most one default value, %d given.',
                \count($default),
            ));
        }

        $scalar = $this->fetchOne();

        // Try next one to determine if result is unique.
        if (false !== $scalar && false !== $this->fetchOne()) {
            throw new NonUniqueResultException('Expected only one result for given query, multiple retrieved.');
        }

        if (false !== $scalar) {
            return $scalar;
        }

        return 0 < \count($default) ? $default[0] : throw new NoResultException('Expected one result for given query, none retrieved.');
    }

    /**
     * {@inheritdoc}
     */
    public function getVector(mixed ...$default): mixed
    {
        $this->assertResultAvailable(__METHOD__);

        if (\count($default) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Expected at most one default value, %d given.',
                \count($default),
            ));
        }

        $result = [];

        while (false !== ($val = $this->fetchOne())) {
            $result[] = $val;
        }

        if (0 !== \count($result)) {
            return $result;
        }

        return 0 < \count($default) ? $default[0] : $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecord(mixed ...$default): mixed
    {
        $this->assertResultAvailable(__METHOD__);

        if (\count($default) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'Expected at most one default value, %d given.',
                \count($default),
            ));
        }

        $row = $this->fetchAssociative();

        // Try next one to determine if result is unique.
        if (false !== $row && false !== $this->fetchAssociative()) {
            throw new NonUniqueResultException('Expected only one result for given query, multiple retrieved.');
        }

        if (false !== $row) {
            return $row;
        }

        return 0 < \count($default) ? $default[0] : throw new NoResultException('Expected one result for given query, none retrieved.');
    }

    /**
     * {@inheritdoc}
     *
     * @return non-negative-int
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function columnCount(): int
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            // @phpstan-ignore-next-line
            return $this->result->columnCount();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while counting columns using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while counting columns using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchAssociative(): array|false
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchAssociative();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching row as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching row as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchNumeric(): array|false
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchNumeric();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching row as numeric array using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching row as numeric array using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchOne(): mixed
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchOne();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching first value of next row using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching first value of next row using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchAllNumeric(): array
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchAllNumeric();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching all rows as numeric array using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching all rows as numeric array using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchAllAssociative(): array
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchAllAssociative();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching all rows as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching all rows as associative array using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function fetchFirstColumn(): array
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            return $this->result->fetchFirstColumn();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching first column using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while fetching first column using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return non-negative-int
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function rowCount(): int
    {
        $this->assertResultAvailable(__METHOD__);

        try {
            // @phpstan-ignore-next-line
            return (int)$this->result->rowCount();
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while counting rows using Doctrine Dbal database driver.',
                $exception,
            );
        } catch (\Exception $exception) { // @phpstan-ignore-line
            throw new RuntimeException(
                'An unexpected error occurred while counting rows using Doctrine Dbal database driver.',
                $exception,
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function count(): int
    {
        $this->assertResultAvailable(__METHOD__);

        if (0 === $this->columnCount()) {
            return $this->rowCount();
        }

        $count = $this->rowCount();

        if ($count > 0) {
            return $count;
        }

        // We can not rely on rowCount() for SELECT statements for all drivers
        // so we need to fetch all results to be sure about the count.
        $this->__sleep();

        return $this->rowCount();
    }

    /**
     * {@inheritdoc}
     *
     * @throws DriverException If a database driver error occurs.
     * @throws RuntimeException If an unexpected error occurs.
     */
    public function getIterator(): \Traversable
    {
        $this->assertResultAvailable(__METHOD__);

        while (false !== ($row = $this->fetchAssociative())) {
            yield $row;
        }
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

        // Kill reference to result to help garbage collection.
        unset($this->result);
    }

    public function __sleep(): array
    {
        $this->assertResultAvailable(__METHOD__);

        if ($this->result instanceof ArrayResult) {
            return ['result'];
        }

        try {
            $columns = [];

            for ($i = 0; $i < $this->result->columnCount(); $i++) {
                $columns[] = $this->result->getColumnName($i);
            }

            $cacheable = new ArrayResult(
                $columns,
                $this->result->fetchAllNumeric()
            );

            // Release original result set resources, as we no longer need it.
            $this->result->free();

            $this->result = $cacheable;
        } catch (DbalException|DbalDriverException $exception) {
            throw new DriverException(
                'An error occurred while fetching results using Doctrine Dbal database driver during serialization.',
                $exception,
            );
        } catch (\Exception $exception) {
            throw new RuntimeException(
                'An unexpected error occurred while fetching results using Doctrine Dbal database driver during serialization.',
                $exception,
            );
        }

        return ['result'];
    }

    /**
     * Asserts that result is still available.
     *
     * After calling free() method, result set is closed and no further
     * operations are allowed.
     *
     * @param string $method Method name that requires open result set.
     *
     * @throws LogicException If result set is closed.
     */
    private function assertResultAvailable(string $method): void
    {
        // @phpstan-ignore-next-line
        if (!isset($this->result)) {
            throw new LogicException(\sprintf(
                'Cannot call method "%s" on closed result set.',
                $method,
            ));
        }
    }
}
