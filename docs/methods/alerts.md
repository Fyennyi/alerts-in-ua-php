# Alerts (Collection)

The `Alerts` class is a powerful wrapper around an array of `Alert` objects. It is returned by most Client methods (like `getActiveAlertsAsync`) and provides fluent methods for filtering and analyzing the data.

## Iteration and Counting

It behaves like a standard array:

```php
$alerts = $client->getActiveAlertsAsync()->wait();

$count = count($alerts); // Implements Countable
foreach ($alerts as $alert) { // Implements IteratorAggregate
    // ...
}
```

## Metadata

The API response often includes metadata about the request.

- **`getLastUpdatedAt()`**: When the data was last refreshed on the server.
- **`getDisclaimer()`**: Mandatory disclaimer text.

## Filtering

The core strength of this class is its filtering capabilities. All filter methods return a **new `array` of `Alert` objects**, preserving the original collection.

### By Location Level

Quickly isolate alerts by administrative level.

```php
$oblasts  = $alerts->getOblastAlerts();  // Only whole oblasts
$raions   = $alerts->getRaionAlerts();   // Only districts
$cities   = $alerts->getCityAlerts();    // Only specific cities
$hromadas = $alerts->getHromadaAlerts(); // Only territorial communities
```

### By Threat Type

```php
use Fyennyi\AlertsInUa\Model\Enum\AlertType;

$raids    = $alerts->getAirRaidAlerts();
$shelling = $alerts->getArtilleryShellingAlerts();
$specific = $alerts->getAlertsByAlertType(AlertType::NUCLEAR);
```

### Advanced Filtering

#### `filter(mixed ...$args)`

A flexible method to filter by multiple property-value pairs. Supports Enum objects or their string values.

```php
use Fyennyi\AlertsInUa\Model\Enum\AlertType;
use Fyennyi\AlertsInUa\Model\Enum\LocationType;

// Find all air raids in Kyiv Oblast
$results = $alerts->filter(
    'location_oblast', 'Київська область',
    'alert_type', AlertType::AIR_RAID
);
```

#### Specific Lookups

```php
// Find by exact location name
$kyiv = $alerts->getAlertsByLocationTitle('м. Київ');

// Find by internal UID
$myRegion = $alerts->getAlertsByLocationUid('12345');
```

## Serialization

The class implements `JsonSerializable` and `__toString()`, both returning a JSON representation of the entire collection.

```php
echo (string) $alerts; // JSON string
```
