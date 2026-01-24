# Caching Strategies

The library includes a sophisticated caching mechanism powered by `async-cache-php`, designed to balance data freshness with API rate limits and performance in an asynchronous environment.

## Overview

When you pass a PSR-16 cache implementation to the client constructor, the client uses `AsyncCacheManager` to wrap it and add logic for:
- **Asynchronous Caching**: Handles caching within promise chains without blocking.
- **Rate Limiting**: Prevents making identical requests too frequently.
- **Stale-While-Revalidate**: Can serve stale data if the rate limit is hit (configurable).
- **Conditional Requests**: Uses HTTP `If-Modified-Since` headers to save bandwidth.

## Default TTL Configuration

The library comes with sensible defaults for Time-To-Live (TTL) values, tailored to the volatility of the data.

| Request Type | Default TTL | Description |
| :--- | :--- | :--- |
| `active_alerts` | **30s** | Active alerts change rapidly. |
| `air_raid_status` | **15s** | Status checks need to be near real-time. |
| `air_raid_statuses` | **15s** | Status checks need to be near real-time. |
| `alerts_history` | **5m (300s)** | History data is relatively static. |
| `location_resolver` | **24h** | Geo-coordinates to city mappings almost never change. |

## Customizing TTL

You can override these defaults using `configureCacheTtl`:

```php
$client->configureCacheTtl([
    'active_alerts' => 10,   // Check more frequently
    'alerts_history' => 600, // Cache history for 10 minutes
]);
```

## Rate Limiting Logic

The internal rate limiter enforces a minimum interval of **5 seconds** between identical requests to the API. If you request the same resource twice within 5 seconds:
1. If the cache is fresh, it is returned immediately.
2. If the cache is stale or missing, the request might be delayed or serve stale data (if enabled) to protect your API quota.

## Clearing Cache

You can manually invalidate cache entries using tags, provided your underlying cache implementation supports `invalidateTags` (e.g., Symfony's `TagAwareAdapter`).

```php
// Clear only active alerts cache
$client->clearCache('active_alerts');

// Clear everything
$client->clearCache(['active_alerts', 'alerts_history', 'location_resolver']);
```