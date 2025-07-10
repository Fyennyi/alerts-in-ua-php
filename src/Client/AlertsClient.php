<?php

namespace Fyennyi\AlertsInUa\Client;

use Fiber;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Exception\BadRequestError;
use Fyennyi\AlertsInUa\Exception\ForbiddenError;
use Fyennyi\AlertsInUa\Exception\InternalServerError;
use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Exception\NotFoundError;
use Fyennyi\AlertsInUa\Exception\RateLimitError;
use Fyennyi\AlertsInUa\Exception\UnauthorizedError;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use Fyennyi\AlertsInUa\Util\UserAgent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Utils;
use Psr\Http\Message\ResponseInterface;

class AlertsClient
{
    private Client $client;

    private string $token;

    private string $baseUrl = 'https://api.alerts.in.ua/v1/';

    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    /** @var list<\GuzzleHttp\Promise\PromiseInterface> */
    private array $promises = [];

    /** @var array<int, Fiber<mixed, mixed, mixed, mixed>> */
    private array $fibers = [];

    /**
     * Constructor for alerts.in.ua API client
     *
     * @param  string  $token  API token
     */
<<<<<<< HEAD
    public function __construct(string $token)
=======
    public function __construct(string $token, ?CacheInterface $cache = null)
>>>>>>> feature/smart-cache
    {
        $this->client = new Client;
        $this->token = $token;
    }

    /**
     * Get active alerts using fibers
     *
     * @param  bool  $use_cache  Use cache
     * @return Fiber<mixed, mixed, Alerts, mixed> Fiber with result
     */
    public function getActiveAlerts(bool $use_cache = false) : Fiber
    {
        return $this->createFiber('alerts/active.json', $use_cache, fn ($data) => new Alerts($data));
    }

    /**
     * Get alert history for specific region using fibers
     *
     * @param  string|int  $oblast_uid_or_location_title  Region unique identifier or name
     * @param  string  $period  Alert history period
     * @param  bool  $use_cache  Use cache
     * @return Fiber<mixed, mixed, Alerts, mixed> Fiber with result
     *
     * @throws InvalidParameterException If location is not found
     */
    public function getAlertsHistory(string|int $oblast_uid_or_location_title, string $period = 'week_ago', bool $use_cache = false) : Fiber
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "regions/{$oblast_uid}/alerts/{$period}.json";

        return $this->createFiber($url, $use_cache, fn ($data) => new Alerts($data));
    }

    /**
     * Get air raid alert status for specific region using fibers
     *
     * @param  string|int  $oblast_uid_or_location_title  Region unique identifier or name
     * @param  bool  $oblast_level_only  Return only oblast-level alerts
     * @param  bool  $use_cache  Use cache
     * @return Fiber<mixed, mixed, AirRaidAlertOblastStatus, mixed> Fiber with result
     *
     * @throws InvalidParameterException If location is not found
     */
    public function getAirRaidAlertStatus(string|int $oblast_uid_or_location_title, bool $oblast_level_only = false, bool $use_cache = false) : Fiber
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "iot/active_air_raid_alerts/{$oblast_uid}.json";

        return $this->createFiber($url, $use_cache, function (array $data) use ($oblast_uid) : AirRaidAlertOblastStatus {
            $location_title = (new LocationUidResolver)->resolveLocationTitle($oblast_uid);
            $first_value = '';
            if (count($data) > 0) {
                $first_value = reset($data);
                if (! is_string($first_value)) {
                    $first_value = '';
                }
            }

            return new AirRaidAlertOblastStatus($location_title, $first_value);
        });
    }

    /**
     * Get air raid alert statuses for all regions using fibers
     *
     * @param  bool  $oblast_level_only  Return only oblast-level alerts
     * @param  bool  $use_cache  Use cache
     * @return Fiber<mixed, mixed, AirRaidAlertOblastStatuses, mixed> Fiber with result
     */
    public function getAirRaidAlertStatusesByOblast(bool $oblast_level_only = false, bool $use_cache = false) : Fiber
    {
        return $this->createFiber('iot/active_air_raid_alerts_by_oblast.json', $use_cache, function (array $data) use ($oblast_level_only) : AirRaidAlertOblastStatuses {
            $first_value = '';
            if (count($data) > 0) {
                $first_value = reset($data);
                if (! is_string($first_value)) {
                    $first_value = '';
                }
            }

            return new AirRaidAlertOblastStatuses($first_value, $oblast_level_only);
        });
    }

    /**
     * Create fiber for API request
     *
     * @template T
     *
     * @param  string  $endpoint  API endpoint
     * @param  bool  $use_cache  Use cache
     * @param  callable(array<string, mixed>): T  $processor  Callback to process response data
     * @return Fiber<mixed, mixed, T, mixed> Fiber with result of type T
     *
     * @throws ApiError If response is invalid or unexpected error occurs
     * @throws \Exception If request fails with a known error
     */
    private function createFiber(string $endpoint, bool $use_cache, callable $processor) : Fiber
    {
        if ($use_cache && isset($this->cache[$endpoint])) {
            /** @var array<string, mixed> $cached_data */
            $cached_data = $this->cache[$endpoint];
            /** @var Fiber<mixed, mixed, T, mixed> */
            $fiber = new Fiber(function () use ($processor, $cached_data) {
                /** @var T */
                return $processor($cached_data);
            });
            $fiber->start();
            return $fiber;
        }

        /** @var Fiber<mixed, mixed, T, mixed> */
        $fiber = new Fiber(function () use ($endpoint, $use_cache, $processor) {
            $promise = $this->client->requestAsync('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept'        => 'application/json',
                    'User-Agent'    => UserAgent::getUserAgent(),
                ],
            ]);

            $this->promises[] = $promise;
            Fiber::suspend();

            try {
                $response = $promise->wait();
                if (! $response instanceof ResponseInterface) {
                    throw new ApiError('Invalid response received');
                }

                $body = $response->getBody();
                $data = json_decode($body->getContents(), true);
                if (! is_array($data)) {
                    throw new ApiError('Invalid JSON response received');
                }

                /** @var array<string, mixed> $data */
                if ($use_cache) {
                    $this->cache[$endpoint] = $data;
                }

                /** @var T */
                return $processor($data);
            } catch (\Throwable $e) {
                if ($e instanceof \Exception) {
                    $this->processError($e);
                } else {
                    throw new ApiError('Fatal error: ' . $e->getMessage(), $e->getCode(), $e);
                }
                throw $e;
            }
        });

        $fiber->start();

        if ($fiber->isSuspended()) {
            $this->fibers[] = $fiber;
        }

        return $fiber;
    }

    /**
     * Wait for all async requests to complete
     *
     * @return void
     */
    public function wait() : void
    {
        if (empty($this->promises)) {
            return;
        }

        $results = Utils::settle($this->promises)->wait();
        $this->promises = [];

        foreach ($this->fibers as $key => $fiber) {
            if ($fiber->isSuspended()) {
                $fiber->resume();
            }

            unset($this->fibers[$key]);
        }

        $this->fibers = [];
    }

    /**
     * Process API errors
     *
     * @param  \Exception  $error  Request error
     * @return void
     *
     * @throws UnauthorizedError If the response status is 401
     * @throws ForbiddenError If the response status is 403
     * @throws NotFoundError If the response status is 404
     * @throws RateLimitError If the response status is 429
     * @throws BadRequestError If the response status is 400
     * @throws InternalServerError If the response status is 500
     * @throws ApiError For any other or unknown errors
     */
    private function processError(\Exception $error) : void
    {
        if ($error instanceof RequestException) {
            $response = $error->getResponse();
            if ($response) {
                $code = $response->getStatusCode();
                switch ($code) {
                    case 401:
                        throw new UnauthorizedError('Unauthorized access. Check your API token.');
                    case 403:
                        throw new ForbiddenError('Access forbidden.');
                    case 404:
                        throw new NotFoundError('Resource not found.');
                    case 429:
                        throw new RateLimitError('Rate limit exceeded.');
                    case 400:
                        throw new BadRequestError('Bad request parameters.');
                    case 500:
                        throw new InternalServerError('Internal server error.');
                    default:
                        throw new ApiError('API error: ' . $error->getMessage());
                }
            } else {
                throw new ApiError('Request failed: ' . $error->getMessage());
            }
        } else {
            throw new ApiError('Unknown error: ' . $error->getMessage());
        }
    }

    /**
     * Resolve UID by location name
     *
     * @param  string|int  $identifier  Identifier
     * @return int Location UID
     *
     * @throws InvalidParameterException If location is not found
     */
    private function resolveUid(string|int $identifier) : int
    {
        if (is_string($identifier)) {
            if (ctype_digit($identifier)) {
                return (int) $identifier;
            }

            return (new LocationUidResolver)->resolveUid($identifier);
        }

        return $identifier;
    }
}
