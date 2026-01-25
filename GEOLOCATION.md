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

This method is the most reliable way to check safety at a specific point. It retrieves all active alerts in a single request and matches the coordinates locally using Nominatim.

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
    
    if ($status->isActive()) {
        echo "⚠️ AIR RAID ALERT IN YOUR AREA!\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## How it Works

1. **Nominatim API**: Uses the `fyennyi/nominatim-async` client to query OpenStreetMap (Nominatim) for reverse geocoding with Ukrainian language preference.
2. **2-Step Resolution**:
   - **High Precision Lookup**: Performs a `reverse` geocoding request at zoom level 18 to identify the specific object at the coordinates.
   - **Hierarchy Analysis**: Performs a `details` lookup for the found object to retrieve its full administrative hierarchy (Hromada > Raion > Oblast).
3. **OSM ID Matching**: The library compares the `osm_id` of the hierarchy components against its local database (`locations.json`), ensuring 100% accuracy for supported administrative units.
4. **Caching**: Geocoding results are cached using the client's PSR-16 cache for 24 hours to stay within Nominatim's rate limits and provide instant results for repeated queries.

## Nominatim Limits

- **1 request/second** (the library uses caching and internal rate limiting to stay within safe bounds)
- Note: The 2-step resolution process makes two requests to Nominatim, which might take up to 2 seconds due to rate limiting if not cached.

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

### `getAirRaidAlertStatusByCoordinatesFromAllAsync(float $lat, float $lon, bool $use_cache = false): Promise<AirRaidAlertStatus>`

Returns air raid alert status for a location by coordinates using the bulk status endpoint.

- `$lat` – The latitude of the location.
- `$lon` – The longitude of the location.
- `$use_cache` – Whether to use cached data (default `false`).
