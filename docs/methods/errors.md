# Error Handling

The library uses a dedicated exception hierarchy to map API errors to PHP exceptions. All exceptions belong to the `Fyennyi\AlertsInUa\Exception` namespace.

## Exception Hierarchy

- **`\Exception`**
    - `ApiError` (Base class for all library exceptions)
        - `BadRequestError` (HTTP 400)
        - `UnauthorizedError` (HTTP 401)
        - `ForbiddenError` (HTTP 403)
        - `NotFoundError` (HTTP 404)
        - `RateLimitError` (HTTP 429)
        - `InternalServerError` (HTTP 500)
    - `InvalidParameterException` (Client-side validation errors)

## Handling Strategies

### Basic Try-Catch

Wrap your calls in a try-catch block to handle expected failures gracefully.

```php
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Exception\UnauthorizedError;

try {
    $alerts = $client->getActiveAlertsAsync()->wait();
} catch (UnauthorizedError $e) {
    // Critical: Token is invalid. Alert the admin.
    $logger->critical("API Token failed: " . $e->getMessage());
} catch (ApiError $e) {
    // General error: network down, API error, etc.
    // Maybe show cached data or a friendly error message.
    $logger->error("Failed to fetch alerts: " . $e->getMessage());
}
```

### Specific Scenarios

#### Rate Limits (`RateLimitError`)
If you receive this, you are sending too many requests. The API (or the internal rate limiter) has blocked you. **Do not retry immediately.** Implement an exponential backoff strategy or increase your cache TTL.

#### Not Found (`NotFoundError`)
This typically happens in `getAlertsHistoryAsync` if you provide an invalid Location UID.

#### Invalid Parameters (`InvalidParameterException`)
This is thrown **before** a request is sent, typically during Geolocation matching if coordinates cannot be resolved to a known location UID.

```php
try {
    $client->getAlertsByCoordinatesAsync(0.0, 0.0);
} catch (InvalidParameterException $e) {
    echo "Could not find a location at those coordinates.";
}
```
