<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * Interface for cache storage with support for expiring keys
 */
interface ExpirableCacheInterface extends CacheInterface
{
    /**
     * Clean up expired keys if supported by the implementation
     */
    public function cleanupExpired() : void;
}
