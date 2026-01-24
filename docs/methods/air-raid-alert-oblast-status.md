# AirRaidAlertOblastStatus

This model represents the aggregated status of a top-level region (Oblast). It is crucial for generating "map" views of the country.

## Logic

The status of an oblast can be:
- **`active`**: The entire oblast or significant parts are under alert.
- **`partly`**: Only specific districts (raions) or communities are under alert, but not the whole region.
- **`no_alert`**: No active alerts.

*Note: If `oblast_level_only` was requested as `true`, `partly` will be converted to `no_alert`.*

## Methods

```php
public function getOblast(): string
public function getStatus(): string
```

### Helper Booleans

```php
if ($oblast->isActive()) {
    // Red color on map
} elseif ($oblast->isPartlyActive()) {
    // Orange/Yellow color
} elseif ($oblast->isNoAlert()) {
    // Green color
}
```

## Serialization

The class implements `JsonSerializable` and `__toString()`.

```php
echo (string) $oblast; // Returns JSON: {"oblast":"...","status":"..."}
```
