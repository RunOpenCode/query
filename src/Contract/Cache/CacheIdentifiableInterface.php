<?php

declare(strict_types=1);

namespace RunOpenCode\Component\Query\Contract\Cache;

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
