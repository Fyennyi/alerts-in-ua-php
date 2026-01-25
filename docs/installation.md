# Installation

The recommended way to install the library is via [Composer](https://getcomposer.org/).

## Requirements

- **PHP**: 8.1 or higher.
- **Composer**: For dependency management.

## Installation

=== "Composer (Recommended)"

    Run the following command in your terminal:

    ```bash
    composer require fyennyi/alerts-in-ua-php
    ```

=== "Git / Manual"

    1. Clone the repository:
       ```bash
       git clone https://github.com/Fyennyi/alerts-in-ua-php.git
       cd alerts-in-ua-php
       ```

    2. Install dependencies:
       ```bash
       composer install
       ```

    3. Include the autoloader in your project:
       ```php
       require_once 'alerts-in-ua-php/vendor/autoload.php';
       ```

## Obtaining an API Token

To use this library, you must have a valid API token from **alerts.in.ua**.

1. Go to [devs.alerts.in.ua](https://devs.alerts.in.ua/).
2. Fill out the application form.
3. Your personal token will be sent to you via email.

!!! warning "Security"
    Keep your API token secret. Do not commit it to public repositories. Use environment variables (e.g., `.env` files) to store sensitive credentials.

## Integration Frameworks

### Symfony

You can register the client as a service in `services.yaml`:

```yaml
services:
  Fyennyi\AlertsInUa\Client\AlertsClient:
    arguments:
      $token: '%env(ALERTS_IN_UA_TOKEN)%'
      $cache: '@cache.app' # Optional: Use Symfony's cache pool
```

### Laravel

You can bind the client in a ServiceProvider:

```php
use Fyennyi\AlertsInUa\Client\AlertsClient;

$this->app->singleton(AlertsClient::class, function ($app) {
    return new AlertsClient(
        config('services.alerts_in_ua.token'),
        $app->make('cache.store')
    );
});
```
