<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Cache;

use RunOpenCode\Component\Query\Contract\Executor\ResultInterface;

/**
 * Marks result as cacheable.
 *
 * @see https://www.php.net/manual/en/language.oop5.magic.php#object.sleep
 */
interface CacheableResultInterface extends ResultInterface
{
    /**
     * Prepare result set for caching.
     *
     * Every cacheable result must implement __sleep method to specify
     * which properties should be serialized as well as to prepare
     * result set for caching.
     *
     * It is recommended for method to release underlying resources
     * (i.g. database resultset handles, file handles, etc) in order
     * to make sure that those resources ARE NOT SERIALIZED.
     *
     * This method should, instead of pointer to underlying resources,
     * keep dataset in PHP equivalents (i.g. arrays, objects, etc).
     *
     * Result set after __sleep method must enable further usage
     * of result, as if nothing happened.
     *
     * Example of implementation can be found in {@see \RunOpenCode\Component\Query\Doctrine\Dbal\Result::__sleep()}
     * where database result set is transformed into array of rows.
     */
    public function __sleep(): array;
}