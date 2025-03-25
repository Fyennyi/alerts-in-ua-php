# Alerts.in.ua PHP API Client

The Alerts.in.ua API Client is a PHP library that simplifies access to the alerts.in.ua API service. It provides real-time information about air raid alerts in Ukraine. The library supports asynchronous operations, making it easy to integrate with various applications and services.

## Installation

To install the Alerts.in.ua API Client, run the following command in your terminal:

```bash
composer require alerts-ua/alerts-in-ua-php
```

## Usage

⚠️ Before you can use this library, you need to obtain an API token by visiting [devs.alerts.in.ua](https://devs.alerts.in.ua/).

### Asynchronous Usage

Here's a basic example of how to use the library to get a list of active alerts asynchronously:

```php
require 'vendor/autoload.php';

use AlertsUA\AlertsClient;

$client = new AlertsClient('your_token');

$client->getActiveAlerts()->then(
    function ($data) {
        echo "Alerts: ";
        print_r($data);
    },
    function ($error) {
        echo "Error: " . $error;
    }
);
```

## Methods

### AlertsClient

#### getActiveAlerts()

Fetches a list of active alerts asynchronously.

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
