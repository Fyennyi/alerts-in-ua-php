<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * Interface for cache storage implementations
 */
interface CacheInterface
{
    /**
     * Retrieve cached value by key
     *
     * @param  string  $key  Cache key
     * @return mixed|null Cached value or null if not found or expired
     */
    public function get(string $key) : mixed;

    /**
     * Store a value in cache
     *
     * @param  string  $key  Cache key
     * @param  mixed  $value  Value to store
     * @param  int  $ttl  Time-to-live in seconds (0 = infinite)
     * @return bool True on success, false on failure
     */
    public function set(string $key, mixed $value, int $ttl = 3600) : bool;

    /**
     * Delete a value from cache
     *
     * @param  string  $key  Cache key
     * @return bool True on success, false if key was not found
     */
    public function delete(string $key) : bool;

    /**
     * Clear all cached entries
     *
     * @return bool True on success
     */
    public function clear() : bool;

    /**
     * Determine whether a cache entry exists and is valid
     *
     * @param  string  $key  Cache key
     * @return bool True if exists and not expired
     */
    public function has(string $key) : bool;

    /**
     * Retrieve stale (expired) cache value, if available
     *
     * @param  string  $key  Cache key
     * @return mixed|null Stale value or null if not retrievable
     */
    public function getStale(string $key) : mixed;

    /**
     * Get all cache keys
     *
     * @return list<string> List of all available cache keys
     */
    public function keys() : array;
}
