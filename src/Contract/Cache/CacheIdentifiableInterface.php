<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Cache;

/**
 * Denotes objects which are able to provide cache identity.
 *
 * Interface may be used for the implementation of the repository pattern
 * where methods of repository accepts criteria objects to fetch data.
 */
interface CacheIdentifiableInterface
{
    /**
     * Get cache identity.
     *
     * Get cache identity for execution result which can be identified
     * by values contained by this object.
     */
    public function getCacheIdentity(): CacheIdentityInterface;
}
