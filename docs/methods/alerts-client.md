# AlertsClient

The `AlertsClient` is the central entry point for interacting with the API. It handles the complexities of HTTP requests, authentication, and caching, exposing a clean, high-level API.

## Constructor

```php
public function __construct(
    string $token, 
    ?CacheInterface $cache = null, 
    ?ClientInterface $client = null
)
```

- **`$token`**: Your API token.
- **`$cache`**: (Optional) A [PSR-16 Simple Cache](https://www.php-fig.org/psr/psr-16/) implementation. If provided, the client will automatically cache responses to reduce API calls and latency.
- **`$client`**: (Optional) A custom Guzzle client instance. Useful for mocking in tests or advanced HTTP configurations (timeouts, proxies).

## Methods

### `getActiveAlertsAsync`

Fetches all currently active alerts across Ukraine.

```php
public function getActiveAlertsAsync(bool $use_cache = false): PromiseInterface
```

**Returns:** A Promise resolving to an [`Alerts`](alerts.md) collection.

**Example:**
```php
$promise = $client->getActiveAlertsAsync(use_cache: true);
$alerts = $promise->wait();
echo "Found " . count($alerts) . " active alerts.";
```

---

### `getAlertsHistoryAsync`

Retrieves the history of alerts for a specific region (oblast) or location.

```php
public function getAlertsHistoryAsync(
    string|int $oblast_uid_or_location_title,
    string $period = 'week_ago',
    bool $use_cache = false
): PromiseInterface
```

**Parameters:**
- `$oblast_uid_or_location_title`: The numeric UID of the oblast OR its name (e.g., `3`, `"Київська область"`).
- `$period`: The time range. Defaults to `'week_ago'`. Check API docs for other supported values.
- `$use_cache`: Whether to utilize the cache.

**Returns:** A Promise resolving to an [`Alerts`](alerts.md) collection containing historical alerts.

---

### `getAirRaidAlertStatusAsync`

Get the specific status for a single location (oblast, raion, or city) in a format suitable for IOT devices or status boards.

```php
public function getAirRaidAlertStatusAsync(
    string|int $oblast_uid_or_location_title,
    bool $oblast_level_only = false,
    bool $use_cache = false
): PromiseInterface
```

**Parameters:**
- `$oblast_level_only`: If `true`, ignores alerts in sub-regions (raions/hromadas) and only returns `active` if the *entire* oblast is under alert. If `false`, a sub-region alert might result in a `partly` status for the oblast.

**Returns:** A Promise resolving to an [`AirRaidAlertOblastStatus`](air-raid-alert-oblast-status.md).

---

### `getAirRaidAlertStatusesByOblastAsync`

Optimized method to get the status of all oblasts at once. Useful for generating maps.

```php
public function getAirRaidAlertStatusesByOblastAsync(
    bool $oblast_level_only = false,
    bool $use_cache = false
): PromiseInterface
```

**Returns:** A Promise resolving to an [`AirRaidAlertOblastStatuses`](air-raid-alert-oblast-statuses.md) collection.

---

### `getAirRaidAlertStatusesAsync`

Retrieves a raw list of all locations with their current status.

```php
public function getAirRaidAlertStatusesAsync(bool $use_cache = false): PromiseInterface
```

**Returns:** A Promise resolving to an [`AirRaidAlertStatuses`](air-raid-alert-statuses.md) collection.

## Error Handling

The client throws specific exceptions from the `Fyennyi\AlertsInUa\Exception` namespace, allowing for granular error handling.

| Exception | HTTP Code | Description |
| :--- | :--- | :--- |
| `UnauthorizedError` | 401 | Invalid or missing API token. |
| `ForbiddenError` | 403 | Access denied to the resource. |
| `NotFoundError` | 404 | The requested location or resource does not exist. |
| `RateLimitError` | 429 | Too many requests. Wait before retrying. |
| `InternalServerError` | 500 | API server error. |
| `ApiError` | Other | Generic API error or network failure. |

**Example:**
```php
try {
    $alerts = $client->getActiveAlertsAsync()->wait();
} catch (UnauthorizedError $e) {
    // Refresh token or notify admin
} catch (RateLimitError $e) {
    // Back off and retry later
} catch (ApiError $e) {
    // Log generic error
}
```
