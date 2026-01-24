# Geo-location Feature

This functionality allows you to get alerts by geographic coordinates. It is powered by the `fyennyi/nominatim-async` library and provides truly non-blocking reverse geocoding.

## Usage

### Getting Alert History by Coordinates

```php
<?php

require 'vendor/autoload.php';

use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_api_token');

try {
    $alerts = $client->getAlertsByCoordinatesAsync(49.8397, 24.0297)->wait(); // Lviv

    echo "Last alerts for this area:\n";

    foreach ($alerts->getAllAlerts() as $alert) {
        // alert_type is now an AlertType enum
        echo "{$alert->getAlertType()->value} in {$alert->getLocationTitle()}\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Getting Air Raid Alert Status by Coordinates

```php
<?php

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;

$client = new AlertsClient('your_api_token');

try {
    $status = $client->getAirRaidAlertStatusByCoordinatesAsync(
        50.4501,
        30.5234
    )->wait();

    echo "Location: {$status->getOblast()}\n";
    // status is now an AlertStatus enum
    echo "Status: {$status->getStatus()->value}\n";

    if ($status->isActive()) {
        echo "⚠️ AIR RAID ALERT IN YOUR AREA!\n";
    } else {
        echo "✅ All clear.\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Getting Air Raid Alert Status by Coordinates (Bulk Method)

This method is more efficient when checking status for many coordinates as it retrieves all statuses in a single request and matches the location locally.

```php
<?php

use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_api_token');

try {
    $status = $client->getAirRaidAlertStatusByCoordinatesFromAllAsync(
        46.4825, 
        30.7233
    )->wait(); // Odesa

    echo "Location: {$status->getLocationTitle()}\n";
    echo "Status: {$status->getStatus()->value}\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## How it Works

1. **Nominatim API**: Uses the `fyennyi/nominatim-async` client to query OpenStreetMap (Nominatim) for reverse geocoding with Ukrainian language preference.
2. **Hierarchical Resolution**: The library performs hierarchical reverse geocoding requests to determine the location:
   - **Zoom 10**: Checks if the coordinates belong to a specific city or municipality (hromada) by its OSM Relation ID.
   - **Zoom 8**: If no hromada is found, it falls back to checking the district level.
   - **Zoom 5**: If no district is found, it falls back to the oblast (state) level.
3. **OSM ID Matching**: Nominatim's `osm_id` is matched against a local database (`locations.json`), ensuring 100% accuracy for supported administrative units.
4. **Caching**: Geocoding results are cached using the client's PSR-16 cache for 24 hours to stay within Nominatim's rate limits and provide instant results for repeated queries.

## Nominatim Limits

- **1 request/second** (the library uses caching and internal rate limiting to stay within safe bounds)
- Ensure you provide a descriptive `User-Agent` if you are using a custom Guzzle client.

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

---

### `getAirRaidAlertStatusByCoordinatesFromAllAsync(float $lat, float $lon, bool $use_cache = false): Promise<AirRaidAlertStatus>`

Returns air raid alert status for a location by coordinates using the bulk status endpoint.

- `$lat` – The latitude of the location.
- `$lon` – The longitude of the location.
- `$use_cache` – Whether to use cached data (default `false`).
