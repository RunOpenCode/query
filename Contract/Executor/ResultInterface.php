<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Executor;

use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\InvalidArgumentException;
use RunOpenCode\Component\Query\Exception\NonUniqueResultException;
use RunOpenCode\Component\Query\Exception\NoResultException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Execution result.
 *
 * Holds the records of query execution
 *
 * Concrete implementations may vary depending on the underlying data source.
 * Implementations must be traversable, countable and serializable in order
 * to support caching mechanisms.
 *
 * Ideally, implementation should support lazy iteration to efficiently handle large datasets.
 *
 * @extends \Traversable<array-key, mixed>
 */
interface ResultInterface extends \Traversable, \Countable
{
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
     * @template T
     *
     * @param T ...$default Optional default value to return if no result is found.
     *
     * @return mixed|T A single scalar value, or default value, if provided and no result found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function getScalar(mixed ...$default): mixed;

    /**
     * Get vector of values.
     *
     * Assuming that your result contains multiple records with at least one field,
     * this method will return a list of values from the first field of each record.
     *
     * This is particularly useful for queries that are expected to return a
     * list of values, such as when querying for a specific column across multiple rows.
     *
     * @template T
     *
     * @param T ...$default Optional default value to return if no results are found.
     *
     * @return list<mixed>|T List of scalar values, or default value if provided and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws DriverException If there is a underlying driver error.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function getVector(mixed ...$default): mixed;

    /**
     * Get single record from result set.
     *
     * Assuming that your result contains one record with multiple fields,
     * this method will return that record.
     *
     * @template T
     *
     * @param T ...$default Optional default value to return if no results are found.
     *
     * @return array<array-key, mixed>|T A single (first) record of result set, or default value if provided and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function getRecord(mixed ...$default): mixed;

    /**
     * Free resources associated with this result.
     *
     * This method should be called when the result is no longer needed to
     * release any underlying resources, such as database cursors or
     * memory allocations.
     */
    public function free(): void;
}
