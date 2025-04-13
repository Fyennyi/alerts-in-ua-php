<?php

namespace AlertsUA;

use GuzzleHttp\Client;
use React\Promise\Deferred;
use React\EventLoop\Factory;
use React\Promise\PromiseInterface;
use GuzzleHttp\Exception\RequestException;

class AlertsClient
{
    private $client;

    private $loop;

    private $token;

    private $baseUrl = 'https://api.alerts.in.ua/v1/';

    private $cache = [];

    public function __construct($token)
    {
        $this->client = new Client;
        $this->loop = Factory::create();
        $this->token = $token;
    }

    /**
     * Makes an asynchronous request to the API
     *
     * @param  string  $endpoint
     * @param  bool  $use_cache
     * @return PromiseInterface
     */
    private function request($endpoint, $use_cache = true)
    {
        $deferred = new Deferred;

        if ($use_cache && isset($this->cache[$endpoint])) {
            $deferred->resolve($this->cache[$endpoint]);

            return $deferred->promise();
        }

        try {
            $promise = $this->client->requestAsync('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                    'User-Agent' => UserAgent::getUserAgent(),
                ],
            ]);

            $promise->then(
                function ($response) use ($deferred, $endpoint, $use_cache) {
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($use_cache) {
                        $this->cache[$endpoint] = $data;
                    }
                    $deferred->resolve($data);
                },
                function ($error) use ($deferred) {
                    if ($error instanceof RequestException) {
                        $response = $error->getResponse();
                        if ($response) {
                            $code = $response->getStatusCode();
                            switch ($code) {
                                case 401:
                                    $deferred->reject(new UnauthorizedError('Unauthorized access. Check your API token.'));
                                    break;
                                case 403:
                                    $deferred->reject(new ForbiddenError('Access forbidden.'));
                                    break;
                                case 404:
                                    $deferred->reject(new NotFoundError('Resource not found.'));
                                    break;
                                case 429:
                                    $deferred->reject(new RateLimitError('Rate limit exceeded.'));
                                    break;
                                case 400:
                                    $deferred->reject(new BadRequestError('Bad request parameters.'));
                                    break;
                                default:
                                    $deferred->reject(new ApiError('API error: ' . $error->getMessage()));
                            }
                        } else {
                            $deferred->reject(new ApiError('Request failed: ' . $error->getMessage()));
                        }
                    } else {
                        $deferred->reject(new ApiError('Unknown error: ' . $error->getMessage()));
                    }
                }
            );
        } catch (\Exception $e) {
            $deferred->reject(new ApiError('Error creating request: ' . $e->getMessage()));
        }

        return $deferred->promise();
    }

    /**
     * Resolves UID by location name
     *
     * @param  string|int  $identifier
     * @return int
     */
    private function resolveUid($identifier)
    {
        if (is_string($identifier)) {
            if (ctype_digit($identifier)) {
                return (int) $identifier;
            } else {
                return (new LocationUidResolver)->resolveUid($identifier);
            }
        }

        return $identifier;
    }

    /**
     * Gets active alerts list asynchronously
     *
     * @param  bool  $use_cache
     * @return PromiseInterface
     */
    public function getActiveAlerts($use_cache = true)
    {
        return $this->request('alerts/active.json', $use_cache)
            ->then(fn ($data) => new Alerts($data));
    }

    /**
     * Gets alert history for specified region
     *
     * @param  string|int  $oblast_uid_or_location_title
     * @param  string  $period
     * @param  bool  $use_cache
     * @return PromiseInterface
     */
    public function getAlertsHistory($oblast_uid_or_location_title, $period = 'week_ago', $use_cache = true)
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "regions/{$oblast_uid}/alerts/{$period}.json";

        return $this->request($url, $use_cache)
            ->then(fn ($data) => new Alerts($data));
    }

    /**
     * Gets air raid alert status for specific region
     *
     * @param  string|int  $oblast_uid_or_location_title
     * @param  bool  $oblast_level_only
     * @param  bool  $use_cache
     * @return PromiseInterface
     */
    public function getAirRaidAlertStatus($oblast_uid_or_location_title, $oblast_level_only = false, $use_cache = true)
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);

        return $this->request("iot/active_air_raid_alerts/{$oblast_uid}.json", $use_cache)
            ->then(function ($data) use ($oblast_uid) {
                return new AirRaidAlertOblastStatus(
                    (new LocationUidResolver)->resolveLocationTitle($oblast_uid),
                    $data
                );
            });
    }

    /**
     * Gets air raid alert statuses for all regions
     *
     * @param  bool  $oblast_level_only
     * @param  bool  $use_cache
     * @return PromiseInterface
     */
    public function getAirRaidAlertStatusesByOblast($oblast_level_only = false, $use_cache = true)
    {
        return $this->request('iot/active_air_raid_alerts_by_oblast.json', $use_cache)
            ->then(fn ($data) => new AirRaidAlertOblastStatuses($data, $oblast_level_only));
    }

    /**
     * Synchronous active alerts retrieval
     *
     * @param  bool  $use_cache
     * @return Alerts
     */
    public function getActiveAlertsSync($use_cache = true)
    {
        $promise = $this->getActiveAlerts($use_cache);

        return $promise->wait();
    }

    /**
     * Synchronous alert history retrieval
     *
     * @param  string|int  $oblast_uid_or_location_title
     * @param  string  $period
     * @param  bool  $use_cache
     * @return Alerts
     */
    public function getAlertsHistorySync($oblast_uid_or_location_title, $period = 'week_ago', $use_cache = true)
    {
        $promise = $this->getAlertsHistory($oblast_uid_or_location_title, $period, $use_cache);

        return $promise->wait();
    }

    /**
     * Synchronous air raid alert status retrieval for specific region
     *
     * @param  string|int  $oblast_uid_or_location_title
     * @param  bool  $oblast_level_only
     * @param  bool  $use_cache
     * @return AirRaidAlertOblastStatus
     */
    public function getAirRaidAlertStatusSync($oblast_uid_or_location_title, $oblast_level_only = false, $use_cache = true)
    {
        $promise = $this->getAirRaidAlertStatus($oblast_uid_or_location_title, $oblast_level_only, $use_cache);

        return $promise->wait();
    }

    /**
     * Synchronous air raid alert statuses retrieval for all regions
     *
     * @param  bool  $oblast_level_only
     * @param  bool  $use_cache
     * @return AirRaidAlertOblastStatuses
     */
    public function getAirRaidAlertStatusesByOblastSync($oblast_level_only = false, $use_cache = true)
    {
        $promise = $this->getAirRaidAlertStatusesByOblast($oblast_level_only, $use_cache);

        return $promise->wait();
    }
}
