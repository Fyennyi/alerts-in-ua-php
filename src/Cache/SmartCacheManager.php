<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * Smart cache manager that supports type-based TTL and rate limiting
 */
class SmartCacheManager
{
    private CacheInterface $cache;

    /** @var array<string, int> Time-to-live in seconds per request type */
    private array $ttl_config = [
        'active_alerts' => 30,
        'air_raid_status' => 15,
        'air_raid_statuses' => 15,
        'alerts_history' => 300,
        'location_resolver' => 86400,
    ];

    /** @var array<string, int> Last request time by cache key */
    private array $last_request_time = [];

    public function __construct(CacheInterface $cache = null)
    {
        $this->cache = $cache ?? new InMemoryCache();
    }

    /**
     * Get cached value or use fallback callback if expired or missing
     *
     * @param  string  $key  Cache key
     * @param  callable(): mixed  $callback  Callback to generate fresh data
     * @param  string  $type  Request type (for TTL)
     * @param  bool  $use_cache  Whether to use cache
     * @return mixed Cached or fresh result
     */
    public function getOrSet(string $key, callable $callback, string $type = 'default', bool $use_cache = true) : mixed
    {
        if (! $use_cache) {
            return $callback();
        }

        $cached = $this->cache->get($key);
        if (null !== $cached) {
            return $cached;
        }

        if ($this->isRateLimited($key)) {
            $stale = $this->getStaleData($key);
            if (null !== $stale) {
                return $stale;
            }
        }

        $data = $callback();
        $ttl = $this->ttl_config[$type] ?? 300;

        $this->cache->set($key, $data, $ttl);
        $this->last_request_time[$key] = time();

        return $data;
    }

    /**
     * Invalidate cache entries matching a pattern (currently clears all)
     *
     * @param  string  $pattern Pattern or wildcard (e.g. '*')
     * @return void
     */
    public function invalidatePattern(string $pattern) : void
    {
        // TODO: Pattern-matching invalidation if supported
        $this->cache->clear();
    }

    /**
     * Set a specific TTL for a request type
     *
     * @param  string  $type  Request type
     * @param  int  $ttl  Time-to-live in seconds
     * @return void
     */
    public function setTtl(string $type, int $ttl) : void
    {
        $this->ttl_config[$type] = $ttl;
    }

    /**
     * Check if request is rate-limited by last request timestamp
     *
     * @param  string  $key  Cache key
     * @return bool True if too soon to repeat request
     */
    private function isRateLimited(string $key) : bool
    {
        if (! isset($this->last_request_time[$key])) {
            return false;
        }

        $min_interval = 5; // seconds

        return (time() - $this->last_request_time[$key]) < $min_interval;
    }

    /**
     * Attempt to return stale (expired) data — if supported
     *
     * @param  string  $key  Cache key
     * @return mixed|null Stale value or null if unavailable
     */
    private function getStaleData(string $key) : mixed
    {
        // Not implemented for InMemoryCache
        return null;
    }
}
