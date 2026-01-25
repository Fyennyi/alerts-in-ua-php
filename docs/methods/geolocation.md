# Geolocation

The library includes powerful geolocation capabilities, allowing you to query alert status based on GPS coordinates (`latitude`, `longitude`) rather than knowing internal UIDs or exact names.

This functionality is provided by the `GeoLocationTrait` which is automatically included in `AlertsClient`.

## How It Works

1. **High-Precision Reverse Geocoding**: The client takes coordinates and queries OpenStreetMap (Nominatim) at zoom level 18 to identify the specific object at that point.
2. **Hierarchy Analysis**: It then performs a `details` lookup to retrieve the full administrative hierarchy (Hromada, Raion, Oblast) for the identified object.
3. **Status Check**: The library matches the OSM IDs from the hierarchy with its internal database (`locations.json`) and retrieves the current alert status for the most specific matched location.

## Methods

### `getAlertsByCoordinatesAsync`

Retrieves the alert history for the location at the given coordinates.

```php
public function getAlertsByCoordinatesAsync(
    float $lat, 
    float $lon, 
    string $period = 'week_ago', 
    bool $use_cache = false
): PromiseInterface
```

**Parameters:**
- `$lat`, `$lon`: GPS coordinates (e.g., `50.4501`, `30.5234` for Kyiv).
- `$period`: History period (default: `'week_ago'`).
- `$use_cache`: Enable caching.

**Returns:** A Promise resolving to an [`Alerts`](alerts.md) collection.

**Example:**
```php
// Check alerts for a user's current position
$alerts = $client->getAlertsByCoordinatesAsync(49.8397, 24.0297) // Lviv
    ->wait();

if (count($alerts) > 0) {
    echo "There were alerts here recently.";
}
```

---

### `getAirRaidAlertStatusByCoordinatesAsync`

Gets the *current* status for the coordinates. This is the most reliable method for checking safety, as it supports granular status checks (communities, districts) by retrieving the full status map.

```php
public function getAirRaidAlertStatusByCoordinatesAsync(
    float $lat, 
    float $lon, 
    bool $use_cache = false
): PromiseInterface
```

**Returns:** A Promise resolving to an [`AirRaidAlertStatus`](air-raid-alert-status.md).

**Example:**
```php
$status = $client->getAirRaidAlertStatusByCoordinatesAsync(48.9226, 24.7111) // Ivano-Frankivsk
    ->wait();

if ($status->isActive()) {
    echo "⚠️ AIR RAID ALERT IN YOUR AREA: " . $status->getLocationTitle();
} else {
    echo "✅ All clear in " . $status->getLocationTitle();
}
```

## Important Considerations

!!! warning "Nominatim Rate Limits"
    The geocoding relies on the public Nominatim API. While the library caches resolutions for **24 hours**, heavy usage without caching might hit Nominatim's rate limits.
    
    The resolver uses a high-precision `reverse` lookup followed by a `details` query to accurately identify the administrative hierarchy (Community > District > Oblast).

!!! info "Caching"
    Geocoding results are cached aggressively (default: 24h) because administrative boundaries rarely change. This makes subsequent requests for the same (or very close) coordinates extremely fast and network-free.
