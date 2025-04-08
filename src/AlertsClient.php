<?php

namespace AlertsUA;

use GuzzleHttp\Client;
use React\Promise\Deferred;
use React\EventLoop\Factory;

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

    private function request($endpoint, $use_cache = true)
    {
        $deferred = new Deferred;

        $this->loop->futureTick(function () use ($deferred, $endpoint, $use_cache) {
            if ($use_cache && isset($this->cache[$endpoint])) {
                $deferred->resolve($this->cache[$endpoint]);

                return;
            }

            $response = $this->client->requestAsync('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                    'User-Agent' => UserAgent::getUserAgent(),
                ],
            ]);

            $response->then(
                function ($response) use ($deferred, $endpoint, $use_cache) {
                    $data = json_decode($response->getBody()->getContents(), true);
                    if ($use_cache) {
                        $this->cache[$endpoint] = $data;
                    }
                    $deferred->resolve($data);
                },
                function ($error) use ($deferred) {
                    $deferred->reject($error->getMessage());
                }
            );
        });

        $this->loop->run();

        return $deferred->promise();
    }

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

    public function getActiveAlerts($use_cache = true)
    {
        return $this->request('alerts/active.json', $use_cache);
    }

    public function getAlertsHistory($oblast_uid_or_location_title, $period = 'week_ago', $use_cache = true)
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "regions/{$oblast_uid}/alerts/{$period}.json";

        return $this->request($url, $use_cache);
    }

    public function getAirRaidAlertStatus($oblast_uid_or_location_title, $oblast_level_only = false, $use_cache = true)
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $data = $this->request("iot/active_air_raid_alerts/{$oblast_uid}.json", $use_cache);

        return new AirRaidAlertOblastStatus((new LocationUidResolver)->resolveLocationTitle($oblast_uid), $data, $oblast_level_only);
    }

    public function getAirRaidAlertStatusesByOblast($oblast_level_only = false, $use_cache = true)
    {
        $data = $this->request('iot/active_air_raid_alerts_by_oblast.json', $use_cache);

        return new AirRaidAlertOblastStatuses($data, $oblast_level_only);
    }
}
