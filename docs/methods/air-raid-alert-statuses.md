# AirRaidAlertStatuses

A read-only collection of `AirRaidAlertStatus` objects. It optimizes lookups by UID.

## Accessing Data

### By UID (Fast Lookup)

```php
$statuses = $client->getAirRaidAlertStatusesAsync()->wait();
$kyivStatus = $statuses->getStatus(31); // 31 is Kyiv City UID
```

### Filtering

You can get subsets of locations based on their status:

```php
$activeLocations = $statuses->getActiveAlertStatuses();
$safeLocations   = $statuses->getNoAlertStatuses();
```

### Array Access

The object implements `ArrayAccess`, but strictly for reading.

```php
if (isset($statuses[31])) {
    $kyiv = $statuses[31];
}
```
