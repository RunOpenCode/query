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
 * @template-covariant TKey of array-key = array-key
 * @template-covariant TRecord of mixed[]|object = mixed[]|object
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
     * Exposes source of the result set.
     * 
     * Upstream exposes the underlying data source or mechanism
     * that produced the result set. This could be a database connection,
     * a file handle, an API client, or any other relevant source.
     * 
     * In case of implementation of decorator, this should expose
     * the upstream decorated result set.
     */
    public mixed $upstream {
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
     * As value objects may be considered as scalars, this method may also
     * return objects (e.g. instance of \DateTimeInterface).
     *
     * @param bool $nullify Instead of throwing NoResultException when no result is found, return NULL.
     *
     * @return ($nullify is true ? scalar|object|null : scalar|object) A single scalar value (or value object), or NULL, if $nullify is TRUE and no result found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function scalar(bool $nullify = false): mixed;

    /**
     * Get vector of values.
     *
     * Assuming that your result contains multiple records with at least one field,
     * this method will return iterable of values from the first field of each record.
     *
     * This is particularly useful for queries that are expected to return a
     * iterable of values, such as when querying for a specific column across multiple rows.
     *
     * As value objects may be considered as scalars, this method may also
     * return iterable of objects (e.g. instances of \DateTimeInterface).
     *
     * @param bool $nullify Instead of returning empty iterable, return NULL.
     *
     * @return ($nullify is true ? iterable<non-negative-int, scalar|object>|null : iterable<non-negative-int, scalar|object>) Iterable of scalar values (or value objects), or NULL, if $nullify is TRUE and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function vector(bool $nullify = false): ?iterable;

    /**
     * Get single record from result set.
     *
     * Assuming that your result contains one record with multiple fields,
     * this method will return that record.
     *
     * Record may be represented as an object or an associative array.
     *
     * @param bool $nullify Instead of throwing NoResultException when no result is found, return NULL.
     *
     * @return ($nullify is empty ? TRecord : TRecord|null) A single (first) record of result set, or NULL, if $nullify is TRUE and no results found.
     *
     * @throws InvalidArgumentException If more than one default value is provided.
     * @throws NoResultException If there are no results of executed statement.
     * @throws NonUniqueResultException If there are more than one result of executed statement.
     * @throws DriverException If there is a underlying driver error.
     * @throws ResultClosedException If result set is closed.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function record(bool $nullify = false): object|array|null;

    /**
     * Get all records.
     *
     * Iterates through all records and place them into an array. Preserves
     * yielded array keys.
     *
     * This is not a memory safe operation.
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
