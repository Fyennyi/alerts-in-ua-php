# Alert

The `Alert` model is an immutable representation of a single alert event. It maps directly to the API response but provides convenient accessor methods and type safety.

## Properties

An `Alert` object contains the following data points:

- **ID**: Unique identifier for the alert.
- **Location Title**: Human-readable name (e.g., "Полтавська область").
- **Location Type**: `LocationType` enum (`oblast`, `raion`, `hromada`, or `city`).
- **Location UID**: Unique system ID for the location.
- **Started At**: DateTime when the alert began.
- **Finished At**: DateTime when the alert ended (or `null` if active).
- **Alert Type**: `AlertType` enum (`air_raid`, `artillery_shelling`, `urban_fights`, etc.).
- **Notes**: Additional context (e.g., "Загроза ракетного удару").

## Accessor Methods

### Basic Getters

```php
public function getId(): int
public function getLocationTitle(): string
public function getLocationType(): LocationType
public function getAlertType(): AlertType
public function getNotes(): ?string
```

### Location Hierarchy

Get information about the parent region (Oblast) or district (Raion).

```php
public function getLocationOblast(): ?string
public function getLocationOblastUid(): ?int
public function getLocationRaion(): ?string
public function getLocationUid(): ?int
```

### Timing Methods

Returns `DateTimeInterface` objects in the appropriate timezone (Kyiv).

```php
public function getStartedAt(): ?\DateTimeInterface
public function getFinishedAt(): ?\DateTimeInterface
public function getUpdatedAt(): ?\DateTimeInterface
```

### Calculated Status
```php
public function isCalculated(): bool
```
Returns `true` if the end time was estimated automatically by the system rather than explicitly signaled by an official source.

## Helper Logic

### `isActive` / `isFinished`

Convenience methods to check the state without manually inspecting dates.

```php
if ($alert->isActive()) {
    echo "⚠️ Alert is ongoing!";
}
```

### `getDuration`

Calculates how long the alert lasted (or has been lasting).

```php
$duration = $alert->getDuration(); // Returns DateInterval
echo $duration->format('%H:%I:%S');
```

To get raw seconds:
```php
$seconds = $alert->getDurationInSeconds();
```

### `isType`

Check the alert category. Supports both `AlertType` enum and raw string values.

```php
use Fyennyi\AlertsInUa\Model\Enum\AlertType;

if ($alert->isType(AlertType::AIR_RAID)) { ... }
if ($alert->isType('air_raid')) { ... }
```

### `isInLocation`

Performs a case-insensitive search to see if the alert affects a specific place (checks title, oblast, and raion names).

```php
if ($alert->isInLocation('Київ')) {
    // Matches "м. Київ", "Київська область", etc.
}
```

## JSON & XML Serialization

The class implements `JsonSerializable`, so you can pass it directly to `json_encode()`. Additionally, you can export the data to XML format.

=== "XML"

    ```php
    echo $alert->toXml('alert');
    ```

=== "JSON"

    ```php
    echo (string) $alert; // Returns JSON string
    echo json_encode($alert);
    ```
