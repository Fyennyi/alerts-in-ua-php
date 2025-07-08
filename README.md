# API Client for alerts.in.ua in PHP

[![Latest Stable Version](https://img.shields.io/packagist/v/fyennyi/alerts-in-ua-php.svg)](https://packagist.org/packages/fyennyi/alerts-in-ua-php)
[![Total Downloads](https://img.shields.io/packagist/dt/fyennyi/alerts-in-ua-php.svg)](https://packagist.org/packages/fyennyi/alerts-in-ua-php)
[![License](https://img.shields.io/packagist/l/fyennyi/alerts-in-ua-php.svg)](https://packagist.org/packages/fyennyi/alerts-in-ua-php)

The API client for alerts.in.ua is a PHP library that simplifies access to the alerts.in.ua API service. It provides real-time information about air raid alerts in Ukraine. The library supports asynchronous operations, making it easy to integrate with various applications and services.

> [!NOTE]
> This unofficial library may not fully support the official alerts.in.ua API and is still in early development, so expect changes or instability.

## Installation

To install the API Client for alerts.in.ua in PHP, run the following command in your terminal:

```bash
composer require fyennyi/alerts-in-ua-php
```

## Usage

⚠️ Before you can use this library, you need to obtain an API token by visiting [devs.alerts.in.ua](https://devs.alerts.in.ua/).

### Basic Setup

First, create a client instance with your API token:

```php
require 'vendor/autoload.php';

use Fyennyi\AlertsInUa\Client\AlertsClient;

$client = new AlertsClient('your_token');
```

### Getting Active Alerts

Here's how to fetch and display all currently active alerts:

```php
try {
    $alerts = $client->getActiveAlertsAsync(false)->wait();

    echo 'Active alerts: ' . count($alerts->getAllAlerts()) . "\n";

    foreach ($alerts->getAllAlerts() as $alert) {
        echo "{$alert->getAlertType()} in {$alert->getLocationTitle()}\n";
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
```

### Getting Alerts History

To retrieve historical alert data for a specific region:

```php
try {
    $history = $client->getAlertsHistoryAsync('Харківська область', 'day_ago', false)->wait();

    echo "\nAlerts history for Kharkiv Oblast: " . count($history->getAllAlerts()) . "\n";

    foreach ($history->getAllAlerts() as $alert) {
        $status = $alert->isFinished() ? 'Finished' : 'Active';
        echo "{$alert->getAlertType()} in {$alert->getLocationTitle()} - {$status}\n";
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
```

### Getting Air Raid Alert Statuses

To check the current status of air raid alerts across all oblasts:

```php
try {
    $statuses = $client->getAirRaidAlertStatusesByOblastAsync(false, false)->wait();

    echo "\nAir raid alert statuses by oblast:\n";

    foreach ($statuses->getStatuses() as $status) {
        echo "{$status->getOblast()}: {$status->getStatus()}\n";
    }
} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
```

### Filtering Alerts

The library provides convenient methods to filter alerts by type and location:

```php
try {
    $alerts = $client->getActiveAlertsAsync(false)->wait();

    // Get only air raid alerts
    $air_raid_alerts = $alerts->getAirRaidAlerts();
    echo "\nAir raid alerts: " . count($air_raid_alerts) . "\n";

    // Get only oblast-level alerts
    $oblast_alerts = $alerts->getOblastAlerts();
    echo "Oblast-level alerts: " . count($oblast_alerts) . "\n";

    // Get alerts for a specific oblast
    $kharkiv_alerts = $alerts->getAlertsByOblast('Харківська область');
    echo "Kharkiv Oblast alerts: " . count($kharkiv_alerts) . "\n";

} catch (\Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
```

## Asynchronous Operations

The library supports asynchronous operations for better performance when handling multiple requests. You can run multiple API calls concurrently without blocking execution.

### Fetching Multiple Alerts Concurrently

You can start multiple requests and handle them all together without blocking:

```php
use GuzzleHttp\Promise\Utils;

$promises = [
    'active' => $client->getActiveAlertsAsync(false),
    'history' => $client->getAlertsHistoryAsync('Харківська область', 'day_ago', false),
];

Utils::all($promises)->then(function ($results) {
    $alerts = $results['active'];
    $history = $results['history'];

    echo "Active alerts: " . count($alerts->getAllAlerts()) . "\n";
    foreach ($alerts->getAllAlerts() as $alert) {
        echo "{$alert->getAlertType()} in {$alert->getLocationTitle()}\n";
    }

    echo "\nHistory for Kharkiv Oblast:\n";
    foreach ($history->getAllAlerts() as $alert) {
        $status = $alert->isFinished() ? 'Finished' : 'Active';
        echo "{$alert->getAlertType()} in {$alert->getLocationTitle()} - {$status}\n";
    }
})->wait();
```

### Checking Air Raid Alert Statuses Concurrently

You can also query multiple oblasts or summary data at the same time:

```php
$promises = [
    'kyiv_status' => $client->getAirRaidAlertStatusAsync('Київська область', true, false),
    'all_statuses' => $client->getAirRaidAlertStatusesByOblastAsync(false, false),
];

Utils::all($promises)->then(function ($results) {
    $kyiv_status = $results['kyiv_status'];
    $all_statuses = $results['all_statuses'];

    echo "Kyiv Oblast air raid status: " . $kyiv_status->getStatus() . "\n";

    echo "\nAir raid alert statuses by oblast:\n";
    foreach ($all_statuses->getStatuses() as $status) {
        echo "{$status->getOblast()}: {$status->getStatus()}\n";
    }
})->wait();
```

### Filtering Alerts After Asynchronous Retrieval

You can apply filters to the alerts once they are asynchronously retrieved:

```php
$client->getActiveAlertsAsync(false)->then(function ($alerts) {
    $air_raid_alerts = $alerts->getAirRaidAlerts();
    $oblast_alerts = $alerts->getOblastAlerts();
    $kharkiv_alerts = $alerts->getAlertsByOblast('Харківська область');

    echo "Air raid alerts: " . count($air_raid_alerts) . "\n";
    echo "Oblast-level alerts: " . count($oblast_alerts) . "\n";
    echo "Kharkiv Oblast alerts: " . count($kharkiv_alerts) . "\n";
})->wait();
```

> ℹ️ **Tip:** You can use `Utils::settle()` instead of `Utils::all()` if you want to gracefully handle individual request failures without throwing exceptions.

You can continue to use individual `->wait()` calls when needed, but using `Utils::all()` allows for better concurrency and performance when dealing with multiple requests.

## Methods

### AlertsClient

#### `getActiveAlertsAsync(bool $use_cache = true): Promise<Alerts>`

Fetches a list of active alerts asynchronously.

- `$use_cache` – Whether to use cached data (default `true`).

---

#### `getAlertsHistoryAsync(string|int $oblast_uid_or_location_title, string $period = 'week_ago', bool $use_cache = true): Promise<Alerts>`

Fetches the alert history for a specific oblast or location.

- `$oblast_uid_or_location_title` – Oblast title or numeric UID.
- `$period` – Time period to retrieve alerts (e.g. `'day_ago'`, `'week_ago'`).
- `$use_cache` – Whether to use cached data (default `true`).

---

#### `getAirRaidAlertStatusAsync(string|int $oblast_uid_or_location_title, bool $oblast_level_only = false, bool $use_cache = true): Promise<AirRaidAlertOblastStatus>`

Returns air raid alert status for one oblast.

- `$oblast_uid_or_location_title` – Oblast title or UID.
- `$oblast_level_only` – Only oblast-level alerts (default `false`).
- `$use_cache` – Use cache (default `true`).

---

#### `getAirRaidAlertStatusesByOblastAsync(bool $oblast_level_only = false, bool $use_cache = true): Promise<AirRaidAlertOblastStatuses>`

Returns air raid alert statuses across all oblasts.

- `$oblast_level_only` – Only oblast-level alerts (default `false`).
- `$use_cache` – Use cache (default `true`).

---

> 🔁 **Note:** All async methods return a `GuzzleHttp\Promise\PromiseInterface`. To retrieve the final result, call `->wait()` on the promise.

## Districts and Regions (UIDs)

[Open the table](https://docs.google.com/spreadsheets/u/0/d/1XnTOzcPHd1LZUrarR1Fk43FUyl8Ae6a6M7pcwDRjNdA/htmlview#)

| UID  | Name                          |
|------|-------------------------------|
| 13   | Івано-Франківська область     |
| 68   | Івано-Франківський район      |
| 67   | Верховинський район           |
| 71   | Калуський район               |
| 70   | Коломийський район            |
| 69   | Косівський район              |
| 72   | Надвірнянський район          |
| 29   | Автономна Республіка Крим     |
| 8    | Волинська область             |
| 38   | Володимирський район          |
| 41   | Камінь-Каширський район       |
| 40   | Ковельський район             |
| 39   | Луцький район                 |
| 4    | Вінницька область             |
| 36   | Вінницький район              |
| 37   | Гайсинський район             |
| 35   | Жмеринський район             |
| 33   | Могилів-Подільський район     |
| 32   | Тульчинський район            |
| 34   | Хмільницький район            |
| 9    | Дніпропетровська область      |
| 44   | Дніпровський район            |
| 42   | Кам'янський район             |
| 46   | Криворізький район            |
| 47   | Нікопольський район           |
| 45   | Павлоградський район          |
| 43   | Самарівський район            |
| 48   | Синельниківський район        |
| 28   | Донецька область              |
| 54   | Бахмутський район             |
| 55   | Волноваський район            |
| 51   | Горлівський район             |
| 53   | Донецький район               |
| 49   | Кальміуський район            |
| 50   | Краматорський район           |
| 52   | Маріупольський район          |
| 56   | Покровський район             |
| 10   | Житомирська область           |
| 57   | Бердичівський район           |
| 59   | Житомирський район            |
| 60   | Звягельський район            |
| 58   | Коростенський район           |
| 11   | Закарпатська область          |
| 61   | Берегівський район            |
| 65   | Мукачівський район            |
| 63   | Рахівський район              |
| 64   | Тячівський район              |
| 66   | Ужгородський район            |
| 62   | Хустський район               |
| 12   | Запорізька область            |
| 564  | м. Запоріжжя                  |
| 147  | Бердянський район             |
| 146  | Василівський район            |
| 149  | Запорізький район             |
| 148  | Мелітопольський район         |
| 145  | Пологівський район            |
| 14   | Київська область              |
| 78   | Бориспільський район          |
| 79   | Броварський район             |
| 75   | Бучанський район              |
| 73   | Білоцерківський район         |
| 74   | Вишгородський район           |
| 76   | Обухівський район             |
| 77   | Фастівський район             |
| 15   | Кіровоградська область        |
| 82   | Голованівський район          |
| 81   | Кропивницький район           |
| 83   | Новоукраїнський район         |
| 80   | Олександрійський район        |
| 16   | Луганська область             |
| 85   | Сватівський район             |
| 86   | Старобільський район          |
| 84   | Сіверськодонецький район      |
| 87   | Щастинський район             |
| 27   | Львівська область             |
| 91   | Дрогобицький район            |
| 94   | Золочівський район            |
| 90   | Львівський район              |
| 88   | Самбірський район             |
| 89   | Стрийський район              |
| 92   | Шептицький район              |
| 93   | Яворівський район             |
| 17   | Миколаївська область          |
| 96   | Баштанський район             |
| 95   | Вознесенський район           |
| 98   | Миколаївський район           |
| 97   | Первомайський район           |
| 18   | Одеська область               |
| 101  | Ізмаїльський район            |
| 100  | Березівський район            |
| 105  | Болградський район            |
| 102  | Білгород-Дністровський район  |
| 104  | Одеський район                |
| 99   | Подільський район             |
| 103  | Роздільнянський район         |
| 19   | Полтавська область            |
| 107  | Кременчуцький район           |
| 106  | Лубенський район              |
| 108  | Миргородський район           |
| 109  | Полтавський район             |
| 5    | Рівненська область            |
| 110  | Вараський район               |
| 111  | Дубенський район              |
| 112  | Рівненський район             |
| 113  | Сарненський район             |
| 20   | Сумська область               |
| 117  | Конотопський район            |
| 118  | Охтирський район              |
| 116  | Роменський район              |
| 114  | Сумський район                |
| 115  | Шосткинський район            |
| 21   | Тернопільська область         |
| 120  | Кременецький район            |
| 119  | Тернопільський район          |
| 121  | Чортківський район            |
| 22   | Харківська область            |
| 1293 | м. Харків                     |
| 125  | Ізюмський район               |
| 127  | Берестинський район           |
| 126  | Богодухівський район          |
| 123  | Куп'янський район             |
| 128  | Лозівський район              |
| 124  | Харківський район             |
| 122  | Чугуївський район             |
| 23   | Херсонська область            |
| 129  | Бериславський район           |
| 133  | Генічеський район             |
| 131  | Каховський район              |
| 130  | Скадовський район             |
| 132  | Херсонський район             |
| 3    | Хмельницька область           |
| 135  | Кам'янець-Подільський район   |
| 134  | Хмельницький район            |
| 136  | Шепетівський район            |
| 24   | Черкаська область             |
| 150  | Звенигородський район         |
| 153  | Золотоніський район           |
| 151  | Уманський район               |
| 152  | Черкаський район              |
| 26   | Чернівецька область           |
| 138  | Вижницький район              |
| 139  | Дністровський район           |
| 137  | Чернівецький район            |
| 25   | Чернігівська область          |
| 144  | Корюківський район            |
| 141  | Новгород-Сіверський район     |
| 142  | Ніжинський район              |
| 143  | Прилуцький район              |
| 140  | Чернігівський район           |
| 31   | м. Київ                       |

## Contributing

Contributions are welcome and appreciated! Here's how you can contribute:

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

Please make sure to update tests as appropriate and adhere to the existing coding style.

## License

This project is licensed under the CSSM Unlimited License v2 (CSSM-ULv2). See the [LICENSE](LICENSE) file for details.
