<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\ResultClosedException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Query execution result.
 *
 * Holds the records of query execution.
 *
 * Concrete implementations may vary depending on the underlying data source.
 *
 * Ideally, implementation should support lazy iteration to efficiently handle
 * large datasets. In order for caching to be supported, implementation must
 * be serializable.
 *
 * Each method for fetching data may be invoked only once, after which result
 * set should free all the resources taken and no further data retrieval is
 * allowed, that is, {@see ResultClosedException} must be thrown.
 *
 * Iterating result set may be executed only once, after which result set
 * becomes closed and {@see ResultClosedException} must be thrown.
 *
 * @template TKey of array-key
 * @template TRecord of mixed
 *
 * @extends \Traversable<TKey, TRecord>
 */
interface ResultInterface extends \Traversable
{
    /**
     * Name of the connection which was used to produce resultset.
     *
     * @var non-empty-string
     */
    public string $connection {
        get;
    }

    /**
     * Check if result set is closed.
     */
    public bool $closed {
        get;
    }

    /**
     * Get single scalar.
     *
     * Assuming that your result contains one record with at least one field,
     * this method will return the value of the first field of that record.
     *
     * This is particularly useful for queries that are expected to return a
     * single value, such as aggregate functions (e.g., COUNT, SUM) or when
     * querying for a specific scalar value.
     *
     * @template TDefault
     *
     * @param TDefault ...$default Optional default value to return if no result is found.
     *
     * @return scalar|TDefault A single scalar value, or default value, if provided and no result found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function scalar(mixed ...$default): mixed;

    /**
     * Get vector of values.
     *
     * Assuming that your result contains multiple records with at least one field,
     * this method will return a list of values from the first field of each record.
     *
     * This is particularly useful for queries that are expected to return a
     * list of values, such as when querying for a specific column across multiple rows.
     *
     * @template TDefault
     *
     * @param TDefault ...$default Optional default value to return if no results are found.
     *
     * @return list<scalar>|TDefault List of scalar values, or default value if provided and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function vector(mixed ...$default): mixed;

    /**
     * Get single record from result set.
     *
     * Assuming that your result contains one record with multiple fields,
     * this method will return that record.
     *
     * @template TDefault
     *
     * @param TDefault ...$default Optional default value to return if no results are found.
     *
     * @return TRecord|TDefault A single (first) record of result set, or default value if provided and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function record(mixed ...$default): mixed;

    /**
     * Get all records.
     *
     * Iterates through all records and place them into an array. Preserves
     * yielded array keys.
     *
     * @return array<TKey, TRecord> All records as array.
     *
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function all(): array;

    /**
     * Free resources associated with this result.
     *
     * This method should be called when the result is no longer needed to
     * release any underlying resources, such as database cursors or
     * memory allocations.
     *
     * Invoking this method must not throw an exception.
     */
    public function free(): void;
}
