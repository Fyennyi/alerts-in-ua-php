# AirRaidAlertStatus

Represents the simple status of a single location, typically used when checking a specific UID or list of UIDs.

## Properties

- **Location Title**: Name of the place.
- **Status**: The simplified state code.
    - `active`: Alert is on.
    - `no_alert`: All clear.
    - `partly`: (Context dependent) Part of the region is under alert.
- **UID**: The unique identifier.

## Usage

```php
$status = $statusCollection->getStatus(12345);

if ($status) {
    echo "{$status->getLocationTitle()}: {$status->getStatus()}";
}
```

## Serialization

The class implements `JsonSerializable` and `__toString()`.

```php
echo (string) $status; // Returns JSON: {"location_title":"...","status":"...","uid":...}
```
