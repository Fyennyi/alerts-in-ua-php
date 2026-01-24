# AirRaidAlertOblastStatuses

A collection of `AirRaidAlertOblastStatus` objects, representing the state of all ~25 major administrative regions of Ukraine.

## Initialization

This class handles the parsing of the compact string format returned by the `getAirRaidAlertStatusesByOblastAsync` API endpoint.

## Filtering

Easily group regions by their state:

```php
$allStatuses = $client->getAirRaidAlertStatusesByOblastAsync()->wait();

$dangerousRegions = $allStatuses->getActiveAlertOblasts();
$partialRegions   = $allStatuses->getPartlyActiveAlertOblasts();
$safeRegions      = $allStatuses->getNoAlertOblasts();

echo "Currently, " . count($dangerousRegions) . " oblasts are under full alert.";
```

## Serialization

- **`jsonSerialize`**: Returns a list of the status objects.
- **`__toString`**: Returns the raw JSON representation.
