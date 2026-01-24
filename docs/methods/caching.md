# Caching Strategies

The library includes a sophisticated caching mechanism called `SmartCacheManager` designed to balance data freshness with API rate limits and performance.

## Overview

When you pass a PSR-16 cache implementation to the client constructor, `SmartCacheManager` wraps it and adds logic for:
- **Rate Limiting**: Prevents making identical requests too frequently (e.g., spamming the refresh button).
- **Conditional Requests**: Uses HTTP `If-Modified-Since` headers to save bandwidth.
- **Tagging**: Allows efficient clearing of related cache items.

## Default TTL Configuration

The library comes with sensible defaults for Time-To-Live (TTL) values, tailored to the volatility of the data.

| Request Type | Default TTL | Description |
| :--- | :--- | :--- |
| `active_alerts` | **30s** | Active alerts change rapidly. |
| `air_raid_status` | **15s** | Status checks need to be near real-time. |
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

The internal rate limiter enforces a minimum interval of **5 seconds** between identical requests to the API, regardless of cache expiration. If you request the same resource twice within 5 seconds, and the cache is expired/missing, the second request might be rejected with a `RateLimitError` (internally handled) or simply wait, protecting your API quota.

## Clearing Cache

You can manually invalidate cache entries using tags. This is useful if you receive a webhook notification that data has changed and want to force a refresh.

```php
// Clear only active alerts cache
$client->clearCache('active_alerts');

// Clear everything
$client->clearCache(['active_alerts', 'alerts_history', 'location_resolver']);
```
