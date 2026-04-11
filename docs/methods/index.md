# API Reference Overview

The **Alerts in UA PHP** library is designed to be intuitive and developer-friendly. It provides a set of classes that map closely to the alerts.in.ua API entities while offering additional helper logic.

## Core Components

### [AlertsClient](alerts-client.md)
The main entry point. Use it to perform all API requests. It supports both synchronous-style usage (via `await()`) and true asynchronous flows.

### [Models](alert.md)
Data is returned as rich objects rather than raw arrays:
- **[Alert](alert.md)**: Details of a single event.
- **[Alerts](alerts.md)**: A filterable collection of Alert objects.
- **[AirRaidAlertStatus](air-raid-alert-status.md)**: Current status codes for specific locations.

## Typical Workflow

1. **Initialize** the client with your token.
2. **Request** data using one of the `Async` methods.
3. **Handle the Result**: You can either block execution or use a non-blocking callback.

=== "Synchronous Style (Simple)"

    The most common way to use the library in scripts. Use `await()` to block until the request is finished.

    ```php
    use function React\Async\await;

    $client = new AlertsClient($token);

    // Blocks execution until results are ready
    $alerts = await($client->getActiveAlertsAsync());

    // Filter for only air raids in a specific oblast
    $kyivRaids = $alerts->getAlertsByOblast('Київська область');
    ```

=== "Async Style (Promises)"

    For performance-critical code or when running multiple requests in parallel.

    ```php
    $client = new AlertsClient($token);

    $client->getActiveAlertsAsync()->then(function($alerts) {
        $kyivRaids = $alerts->getAlertsByOblast('Київська область');
        echo count($kyivRaids) . " alerts in Kyiv.";
    });
    ```
