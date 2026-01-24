# AirRaidAlertStatus

Represents the simple status of a single location, typically used when checking a specific UID or list of UIDs.

## Properties

- **Location Title**: Name of the place.
- **Status**: The simplified state as an `AlertStatus` enum.
    - `AlertStatus::ACTIVE`: Alert is on.
    - `AlertStatus::NO_ALERT`: All clear.
    - `AlertStatus::PARTLY`: (Context dependent) Part of the region is under alert.
- **UID**: The unique identifier.

## Usage

```php
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;

$status = $statusCollection->getStatus(12345);

if ($status && $status->getStatus() === AlertStatus::ACTIVE) {
    echo "{$status->getLocationTitle()} is UNDER ALERT!";
}
```

## Serialization

The class implements `JsonSerializable` and `__toString()`.

```php
echo (string) $status; // Returns JSON: {"location_title":"...","status":"...","uid":...}
```
