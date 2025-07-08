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
        'active_alerts' => 30,        // 30 seconds
        'air_raid_status' => 15,      // 15 seconds
        'air_raid_statuses' => 15,    // 15 seconds
        'alerts_history' => 300,      // 5 minutes
        'location_resolver' => 86400, // 24 hours
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
     * @template T
     *
     * @param  string  $key  Cache key
     * @param  callable(): T  $callback  Callback to generate fresh data
     * @param  string  $type  Request type (for TTL)
     * @param  bool  $use_cache  Whether to use cache
     * @return T Cached or fresh result
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
     * Invalidate cache entries matching a pattern (supports '*' wildcard)
     *
     * @param  string  $pattern  Pattern or wildcard (e.g. 'alerts/*', '*')
     * @return void
     */
    public function invalidatePattern(string $pattern) : void
    {
        $regex = '/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/';

        foreach ($this->cache->keys() as $key) {
            if (preg_match($regex, $key)) {
                $this->cache->delete($key);
                $this->cache->delete($key . '.last_modified');
                $this->cache->delete($key . '.processed');
            }
        }
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
    public function getStaleData(string $key) : mixed
    {
        return $this->cache->getStale($key);
    }

    /**
     * Stores the Last-Modified HTTP header value for the specified cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @param  string  $timestamp  The value of the Last-Modified header (RFC 1123 format)
     * @return void
     */
    public function setLastModified(string $key, string $timestamp): void
    {
        $this->cache->set($key . '.last_modified', $timestamp, 86400);
    }

    /**
     * Retrieves the previously stored Last-Modified header value for a given cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @return string|null The stored Last-Modified header value or null if not available
     */
    public function getLastModified(string $key) : ?string
    {
        $value = $this->cache->get($key . '.last_modified');

        return is_string($value) ? $value : null;
    }

    /**
     * Stores the result of the processed response data for a given cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @param  mixed  $data  The processed result (e.g., Alerts, AirRaidAlertStatus, etc.)
     * @return void
     */
    public function storeProcessedData(string $key, mixed $data) : void
    {
        $this->cache->set($key . '.processed', $data, 86400);
    }

    /**
     * Retrieves the previously stored processed response data for a given cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @return mixed The cached processed result or null if not available
     */
    public function getCachedData(string $key) : mixed
    {
        return $this->cache->get($key . '.processed');
    }
}
