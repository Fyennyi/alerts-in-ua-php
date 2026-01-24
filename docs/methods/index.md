# API Reference Overview

The **Alerts in UA PHP** library is designed to be intuitive and developer-friendly. It provides a set of classes that map closely to the alerts.in.ua API entities while offering additional helper logic.

## Core Components

### [AlertsClient](alerts-client.md)
The main entry point. Use it to perform all API requests. It supports both synchronous-style usage (via `.wait()`) and true asynchronous flows.

### [Models](alert.md)
Data is returned as rich objects rather than raw arrays:
- **[Alert](alert.md)**: Details of a single event.
- **[Alerts](alerts.md)**: A filterable collection of Alert objects.
- **[AirRaidAlertStatus](air-raid-alert-status.md)**: Current status codes for specific locations.

## Typical Workflow

1. **Initialize** the client with your token.
2. **Request** data using one of the `Async` methods.
3. **Wait** for the results or handle the promise.
4. **Filter** or process the resulting models.

```php
$client = new AlertsClient($token);

// Get all active alerts
$alerts = $client->getActiveAlertsAsync()->wait();

// Filter for only air raids in a specific oblast
$kyivRaids = $alerts->getAlertsByOblast('Київська область');
```
