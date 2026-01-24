# Geolocation

The library includes powerful geolocation capabilities, allowing you to query alert status based on GPS coordinates (`latitude`, `longitude`) rather than knowing internal UIDs or exact names.

This functionality is provided by the `GeoLocationTrait` which is automatically included in `AlertsClient`.

## How It Works

1. **Reverse Geocoding**: The client takes coordinates and queries OpenStreetMap (Nominatim) to find the corresponding administrative area (City, Hromada, Raion, or Oblast).
2. **Matching**: It attempts to match the Nominatim result with the library's internal database of location UIDs (`locations.json`).
3. **Status Check**: Once the UID is resolved, it acts just like a standard status request.

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

Gets the *current* status for the coordinates. This is ideal for "Is it safe here?" checks.

```php
public function getAirRaidAlertStatusByCoordinatesAsync(
    float $lat, 
    float $lon, 
    bool $oblast_level_only = false, 
    bool $use_cache = false
): PromiseInterface
```

**Returns:** A Promise resolving to an [`AirRaidAlertOblastStatus`](air-raid-alert-oblast-status.md).

**Example:**
```php
$status = $client->getAirRaidAlertStatusByCoordinatesAsync(48.9226, 24.7111) // Ivano-Frankivsk
    ->wait();

if ($status->isActive()) {
    echo "⚠️ AIR RAID ALERT IN YOUR AREA!";
} else {
    echo "✅ All clear.";
}
```

---

### `getAirRaidAlertStatusByCoordinatesFromAllAsync`

Gets the *current* status for the coordinates using the bulk status endpoint. This method is more efficient when checking status for many coordinates as it retrieves all statuses in a single request and matches the location locally.

```php
public function getAirRaidAlertStatusByCoordinatesFromAllAsync(
    float $lat, 
    float $lon, 
    bool $use_cache = false
): PromiseInterface
```

**Returns:** A Promise resolving to an [`AirRaidAlertStatus`](air-raid-alert-status.md).

**Example:**
```php
$status = $client->getAirRaidAlertStatusByCoordinatesFromAllAsync(46.4825, 30.7233) // Odesa
    ->wait();

echo "Status for {$status->getLocationTitle()}: {$status->getStatus()->value}";
```

## Important Considerations

!!! warning "Nominatim Rate Limits"
    The geocoding relies on the public Nominatim API. While the library caches resolutions for **24 hours**, heavy usage without caching might hit Nominatim's rate limits.
    
    The resolver automatically attempts different zoom levels (10 -> 8 -> 5) to find a matching administrative unit if the exact point isn't matched.

!!! info "Caching"
    Geocoding results are cached aggressively (default: 24h) because administrative boundaries rarely change. This makes subsequent requests for the same (or very close) coordinates extremely fast and network-free.
