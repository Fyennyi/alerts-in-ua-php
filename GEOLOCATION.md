# Geo-location Feature

This functionality allows you to get alerts by geographic coordinates.

## Usage

### Getting Alert History by Coordinates

```php
<?php

require 'vendor/autoload.php';

use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_api_token');

try {
    $alerts = $client->getAlertsByCoordinatesAsync(49.9935, 36.2304)->wait();

    echo "Found alerts for location: {$alerts->getDisclaimer()}\n";

    foreach ($alerts->getAllAlerts() as $alert) {
        echo "{$alert->getAlertType()} in {$alert->getLocationTitle()}\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Getting Air Raid Alert Status by Coordinates

```php
<?php

use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_api_token');

try {
    $status = $client->getAirRaidAlertStatusByCoordinatesAsync(
        50.4501,
        30.5234
    )->wait();

    echo "Location: {$status->getLocationTitle()}\n";
    echo "Status: {$status->getStatus()}\n";

    if ($status->isActive()) {
        echo "Air raid alert is ACTIVE!\n";
    } else {
        echo "No air raid alert\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## How it Works

1. **Nominatim API**: Free OpenStreetMap API for reverse geocoding (no key required) with Ukrainian language preference.
2. **Hierarchical Resolution**: The library performs hierarchical reverse geocoding requests to determine the location:
   - **Zoom 10**: Checks if the coordinates belong to a specific city or municipality (hromada) by its OSM Relation ID.
   - **Zoom 8**: If no hromada is found, it falls back to checking the district (raion) level.
   - **Zoom 5**: If no district is found, it falls back to the oblast (state) level.
3. **OSM ID Matching**: Nominatim's `osm_id` is matched against a local database, ensuring 100% accuracy for supported administrative units.
4. **Caching**: Geocoding results are cached using the client's PSR-16 cache for 24 hours to stay within Nominatim's rate limits.

## Nominatim Limits

- **1 request/second** (the library uses caching to minimize API calls)
- Add `User-Agent` with your project name via `AIU_USER_AGENT` environment variable if needed

## Caching

Geo-results are cached using the same PSR-16 cache as the main client for 24 hours.

## API

### `getAlertsByCoordinatesAsync(float $lat, float $lon, string $period = 'week_ago', bool $use_cache = false): Promise<Alerts>`

Fetches the alert history for a location by coordinates.

- `$lat` – The latitude of the location.
- `$lon` – The longitude of the location.
- `$period` – Time period to retrieve alerts (e.g. `'month_ago'`, `'week_ago'`).
- `$use_cache` – Whether to use cached data (default `false`).

---

### `getAirRaidAlertStatusByCoordinatesAsync(float $lat, float $lon, bool $oblast_level_only = false, bool $use_cache = false): Promise<AirRaidAlertOblastStatus>`

Returns air raid alert status for a location by coordinates.

- `$lat` – The latitude of the location.
- `$lon` – The longitude of the location.
- `$oblast_level_only` – Only oblast-level alerts (default `false`).
- `$use_cache` – Whether to use cached data (default `false`).
