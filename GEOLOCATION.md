# Geo-location Feature

This functionality allows you to get alerts by geographic coordinates.

## Installation

```bash
composer require ashtokalo/php-translit mjaschen/phpgeo
```

## Name Mapping Generation (one time)

```bash
composer run-script generate-mapping
```

This will create the `src/Model/name_mapping.json` file with transliterated names.

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

1. **Transliteration**: Ukrainian names are converted to Latin using `ashtokalo/php-translit`
2. **Nominatim API**: Free OpenStreetMap API for reverse geocoding (no key required)
3. **Automatic Matching**: English names from Nominatim are automatically matched with Ukrainian ones through fuzzy matching (similarity > 70%)
4. **Caching**: Geocoding results are cached using the client's PSR-16 cache for 24 hours

## Nominatim Limits

- **1 request/second**
- Please use caching
- Add `User-Agent` with your project name

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
