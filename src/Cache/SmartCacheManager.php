<?php

/*
 *
 *     _    _           _       ___       _   _
 *    / \  | | ___ _ __| |_ ___|_ _|_ __ | | | | __ _
 *   / _ \ | |/ _ \ '__| __/ __|| || '_ \| | | |/ _` |
 *  / ___ \| |  __/ |  | |_\__ \| || | | | |_| | (_| |
 * /_/   \_\_|\___|_|   \__|___/___|_| |_|\___/ \__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author Serhii Cherneha
 * @link https://chernega.eu.org/
 *
 *
 */

namespace Fyennyi\AlertsInUa\Cache;

use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
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

    /**
     * @param  SymfonyCacheInterface|null  $cache  The cache instance to use. Defaults to a TagAwareAdapter with an ArrayAdapter
     */
    public function __construct(SymfonyCacheInterface $cache = null)
    {
        $this->cache = $cache ?? new TagAwareAdapter(new ArrayAdapter());
    }

    /**
     * Get cached value or use fallback callback if expired or missing
     *
     * @param  string  $key  Cache key
     * @param  callable(): PromiseInterface  $callback  Callback that returns a promise for the fresh data
     * @param  string  $type  Request type (for TTL and tags)
     * @param  bool  $use_cache  Whether to use cache
     * @return PromiseInterface Cached or fresh result
     */
    public function getOrSet(string $key, callable $callback, string $type = 'default', bool $use_cache = true) : PromiseInterface
    {
        if (! $use_cache) {
            return Create::promiseFor($callback());
        }

        $cached_value = $this->cache->get($key, fn() => null);
        if ($cached_value !== null) {
            return Create::promiseFor($cached_value);
        }

        if ($this->isRateLimited($key)) {
            return Create::rejectionFor('Rate limit exceeded for key: ' . $key);
        }

        $promise = $callback();

        return $promise->then(
            function ($data) use ($key, $type) {
                $ttl = $this->ttl_config[$type] ?? 300;

                $this->cache->delete($key);
                $this->cache->get($key, function(ItemInterface $item) use ($data, $ttl, $type) {
                    $item->expiresAfter($ttl);
                    if ($this->cache instanceof TagAwareCacheInterface) {
                        $sanitized_tag = str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $type);
                        $item->tag($sanitized_tag);
                    }
                    return $data;
                });

                return $data;
            }
        );
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
     * Stores the Last-Modified HTTP header value for the specified cache key
     *
     * @param  string  $key  The base cache key associated with the API endpoint
     * @param  string  $timestamp  The value of the Last-Modified header (RFC 1123 format)
     * @return void
     */
    public function setLastModified(string $key, string $timestamp) : void
    {
        $cache_key = $key . '.last_modified';
        $this->cache->delete($cache_key);
        $this->cache->get($cache_key, function (ItemInterface $item) use ($key, $timestamp) {
            $item->expiresAfter(86400);
            if ($this->cache instanceof TagAwareCacheInterface) {
                $sanitized_tag = str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $key);
                $item->tag($sanitized_tag);
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
        $cache_key = $key . '.processed';
        $this->cache->delete($cache_key);
        $this->cache->get($cache_key, function (ItemInterface $item) use ($key, $data) {
            $item->expiresAfter(86400);
            if ($this->cache instanceof TagAwareCacheInterface) {
                $sanitized_tag = str_replace(['{', '}', '(', ')', '/', '\\', '@', ':'], '_', $key);
                $item->tag($sanitized_tag);
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
