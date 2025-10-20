<?php

namespace Fyennyi\AlertsInUa\Cache;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Contracts\Cache\CacheInterface as SymfonyCacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Smart cache manager that supports type-based TTL and rate limiting
 */
class SmartCacheManager
{
    private SymfonyCacheInterface $cache;

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

    public function __construct(SymfonyCacheInterface $cache = null)
    {
        $this->cache = $cache ?? new TagAwareAdapter(new ArrayAdapter());
    }

    /**
     * Get cached value or use fallback callback if expired or missing
     *
     * @template T
     *
     * @param  string  $key  Cache key
     * @param  callable(): T  $callback  Callback to generate fresh data
     * @param  string  $type  Request type (for TTL and tags)
     * @param  bool  $use_cache  Whether to use cache
     * @return T Cached or fresh result
     */
    public function getOrSet(string $key, callable $callback, string $type = 'default', bool $use_cache = true) : mixed
    {
        if (! $use_cache) {
            return $callback();
        }

        if ($this->isRateLimited($key)) {
            // With the new cache backend, we can't easily get stale data
            // when rate-limited, so we return null, effectively bypassing the cache.
            // The caller will then proceed to make a fresh request.
            // A more sophisticated implementation could return a specific response
            // indicating rate limiting.
        }

        $ttl = $this->ttl_config[$type] ?? 300;

        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl, $type) {
            $item->expiresAfter($ttl);

            if ($this->cache instanceof TagAwareCacheInterface) {
                $item->tag($type);
            }

            $this->last_request_time[$item->getKey()] = time();

            return $callback();
        });
    }

    /**
     * Invalidate cache entries by tag
     *
     * @param  string|string[]  $tags  Tag or tags to invalidate
     * @return void
     */
    public function invalidateTags(string|array $tags) : void
    {
        if ($this->cache instanceof TagAwareCacheInterface) {
            $this->cache->invalidateTags(is_string($tags) ? [$tags] : $tags);
        }
    }

    /**
     * Stores the Last-Modified HTTP header value for the specified cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @param  string  $timestamp  The value of the Last-Modified header (RFC 1123 format)
     * @return void
     */
    public function setLastModified(string $key, string $timestamp) : void
    {
        $cacheKey = $key . '.last_modified';
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($key, $timestamp) {
            $item->expiresAfter(86400);
            if ($this->cache instanceof TagAwareCacheInterface) {
                $item->tag($key);
            }
            return $timestamp;
        });
    }

    /**
     * Retrieves the previously stored Last-Modified header value for a given cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @return string|null The stored Last-Modified header value or null if not available
     */
    public function getLastModified(string $key) : ?string
    {
        $value = $this->cache->get($key . '.last_modified', fn() => null);

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
        $cacheKey = $key . '.processed';
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($key, $data) {
            $item->expiresAfter(86400);
            if ($this->cache instanceof TagAwareCacheInterface) {
                $item->tag($key);
            }
            return $data;
        });
    }

    /**
     * Retrieves the previously stored processed response data for a given cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @return mixed The cached processed result or null if not available
     */
    public function getCachedData(string $key) : mixed
    {
        return $this->cache->get($key . '.processed', fn() => null);
    }
}
