# Alerts in UA PHP

[![Latest Stable Version](https://img.shields.io/packagist/v/fyennyi/alerts-in-ua-php.svg?label=Packagist&logo=packagist)](https://packagist.org/packages/fyennyi/alerts-in-ua-php)
[![License](https://img.shields.io/packagist/l/fyennyi/alerts-in-ua-php.svg?label=Licence&logo=open-source-initiative)](https://packagist.org/packages/fyennyi/alerts-in-ua-php)

The **Alerts in UA PHP** library provides a robust, object-oriented interface for the [alerts.in.ua](https://alerts.in.ua/) API. It simplifies the process of retrieving real-time air raid alert data for Ukraine, supporting asynchronous requests, caching, and comprehensive data modeling.

## Key Features

- **Asynchronous Requests:** Built on Guzzle Promises for non-blocking I/O.
- **Smart Caching:** PSR-16 compatible caching to respect API rate limits and improve performance.
- **Rich Models:** Fully typed objects for Alerts, Locations, and Statuses.
- **Helper Methods:** Convenient filtering and data manipulation methods built-in.

## Quick Start

```php
use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_api_token');
$alerts = $client->getActiveAlertsAsync()->wait();

foreach ($alerts as $alert) {
    echo "Alert in {$alert->getLocationTitle()} started at {$alert->getStartedAt()->format('H:i')}\n";
}
```

