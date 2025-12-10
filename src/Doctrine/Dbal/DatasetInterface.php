<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Doctrine\Dbal;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;
use RunOpenCode\Component\Query\Exception\DriverException;
use RunOpenCode\Component\Query\Exception\RuntimeException;

/**
 * Data set is abstraction of the underlying result set
 * retrieved by Doctrine Dbal.
 *
 * Instance of {@see Result} utilises data set to provide
 * data for method defined by {@see ResultInterface}.
 *
 * @phpstan-type Row = array<non-empty-string, scalar|null>
 *
 * @implements \IteratorAggregate<Row>
 *
 * @internal
 */
interface DatasetInterface extends \IteratorAggregate
{
    /**
     * Name of the connection which was used to produce data set.
     *
     * @var non-empty-string
     */
    public string $connection {
        get;
    }

    /**
     * Get vector of scalar values.
     *
     * Retrieves a vector of scalar values.
     *
     * @return iterable<scalar> List of scalar values.
     *
     * @throws DriverException If there is a underlying driver error.
     * @throws RuntimeException If unexpected error occurs during result retrieval.
     */
    public function vector(): iterable;

    /**
     * Free resources associated with this data set.
     *
     * This method should be called when the data set is no longer
     * needed. Data set should release any underlying resource.
     *
     * Invoking this method must not throw an exception.
     */
    public function free(): void;
}
