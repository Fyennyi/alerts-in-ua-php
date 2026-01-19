<?php

/*
 *
 *     _    _           _       ___       _   _
 *    / \  | | ___ _ __| |_ ___|_ _|_ __ | | | | __ _
 *   / _ \ | |/ _ \ '__| __/ __|| || '_ \| | | |/ _` |
 *  / ___ \| |  __/ |  | |_\__ \| || | | | |_| | (_| |
 * /_/   \_\_|\___|_|   \__|___/___|_| |_|\___/ \__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author Serhii Cherneha
 * @link https://chernega.eu.org/
 *
 *
 */

namespace Fyennyi\AlertsInUa\Client;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
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
use Fyennyi\AlertsInUa\Model\AirRaidAlertStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertStatuses;
use Fyennyi\AlertsInUa\Model\AirRaidAlertStatusResolver;
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use Fyennyi\AlertsInUa\Util\UserAgent;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class AlertsClient
{
    use GeoLocationTrait;

    /** @var ClientInterface HTTP client for making requests */
    private ClientInterface $client;

    /** @var string API authentication token */
    private string $token;

    /** @var string Base URL for the API */
    private string $base_url = 'https://api.alerts.in.ua/v1/';

    /** @var SmartCacheManager Manages caching of API responses using a PSR-16 compatible cache internally */
    private SmartCacheManager $cache_manager;

    /** @var CacheInterface|null PSR-16 cache instance */
    private ?CacheInterface $cache;

    /**
     * Constructor for alerts.in.ua API client
     *
     * @param  string  $token  API token
     * @param  CacheInterface|null  $cache  Optional PSR-16 compliant cache implementation. If null, a no-op cache is used
     * @param  ClientInterface|null  $client  Optional Guzzle client instance
     */
    public function __construct(string $token, ?CacheInterface $cache = null, ?ClientInterface $client = null)
    {
        $this->client = $client ?? new Client();
        $this->token = $token;

        $symfonyCache = $cache ? new Psr16Adapter($cache) : null;
        $this->cache_manager = new SmartCacheManager($symfonyCache);
        $this->cache = $cache;
    }

    /**
     * Retrieves active alerts asynchronously
     *
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an Alerts object
     */
    public function getActiveAlertsAsync(bool $use_cache = false) : PromiseInterface
    {
        return $this->createAsync('alerts/active.json', $use_cache, function (ResponseInterface $response) {
            $raw_response_body = $response->getBody()->getContents();
            $data = json_decode($raw_response_body, true);
            if (! is_array($data)) {
                throw new ApiError('Invalid JSON response received');
            }

            return new Alerts($data);
        }, 'active_alerts');
    }

    /**
     * Retrieves alert history for a specific region asynchronously
     *
     * @param  string|int  $oblast_uid_or_location_title  Region identifier (UID or name)
     * @param  string  $period  Time period for history (default: 'week_ago')
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an Alerts object
     *
     * @throws InvalidParameterException If the location cannot be resolved
     */
    public function getAlertsHistoryAsync(string|int $oblast_uid_or_location_title, string $period = 'week_ago', bool $use_cache = false) : PromiseInterface
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "regions/{$oblast_uid}/alerts/{$period}.json";

        return $this->createAsync($url, $use_cache, function (ResponseInterface $response) {
            $raw_response_body = $response->getBody()->getContents();
            $data = json_decode($raw_response_body, true);
            if (! is_array($data)) {
                throw new ApiError('Invalid JSON response received');
            }

            return new Alerts($data);
        }, 'alerts_history');
    }

    /**
     * Retrieves air raid alert status for a specific region asynchronously
     *
     * @param  string|int  $oblast_uid_or_location_title  Region identifier (UID or name)
     * @param  bool  $oblast_level_only  Whether to return only oblast-level alerts
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an AirRaidAlertOblastStatus object
     *
     * @throws InvalidParameterException If the location cannot be resolved
     */
    public function getAirRaidAlertStatusAsync(string|int $oblast_uid_or_location_title, bool $oblast_level_only = false, bool $use_cache = false) : PromiseInterface
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "iot/active_air_raid_alerts/{$oblast_uid}.json";
        $cache_key_suffix = ':oblast_level_only=' . ($oblast_level_only ? 'true' : 'false');

        return $this->createAsync($url, $use_cache, function (ResponseInterface $response) use ($oblast_uid, $oblast_level_only): AirRaidAlertOblastStatus {
            $raw_response_body = $response->getBody()->getContents();
            $data = json_decode($raw_response_body, true);
            if (! is_string($data)) {
                throw new ApiError('Invalid response received');
            }

            $location_title = (new LocationUidResolver())->resolveLocationTitle($oblast_uid);

            return new AirRaidAlertOblastStatus($location_title, $data, $oblast_level_only);
        }, 'air_raid_status', $cache_key_suffix);
    }

    /**
     * Retrieves air raid alert statuses for all regions asynchronously
     *
     * @param  bool  $oblast_level_only  Whether to return only oblast-level alerts
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an AirRaidAlertOblastStatuses object
     */
    public function getAirRaidAlertStatusesByOblastAsync(bool $oblast_level_only = false, bool $use_cache = false) : PromiseInterface
    {
        $cache_key_suffix = ':oblast_level_only=' . ($oblast_level_only ? 'true' : 'false');

        return $this->createAsync('iot/active_air_raid_alerts_by_oblast.json', $use_cache, function (ResponseInterface $response) use ($oblast_level_only): AirRaidAlertOblastStatuses {
            $raw_response_body = $response->getBody()->getContents();
            $data = json_decode($raw_response_body, true);
            if (! is_string($data)) {
                return new AirRaidAlertOblastStatuses('', $oblast_level_only);
            }

            $status_string = $data;
            if (27 !== strlen($status_string)) {
                $status_string = substr($status_string, 0, 27);
            }

            return new AirRaidAlertOblastStatuses($status_string, $oblast_level_only);
        }, 'air_raid_statuses', $cache_key_suffix);
    }

    /**
     * Retrieves air raid alert statuses for all regions asynchronously
     *
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an AirRaidAlertStatuses object
     */
    public function getAirRaidAlertStatusesAsync(bool $use_cache = false) : PromiseInterface
    {
        return $this->createAsync('iot/active_air_raid_alerts.json', $use_cache, function (ResponseInterface $response): AirRaidAlertStatuses {
            $data = $response->getBody()->getContents();
            $resolved_statuses = AirRaidAlertStatusResolver::resolveStatusString($data, (new LocationUidResolver())->getUidToLocationMapping());

            $statuses = [];
            foreach ($resolved_statuses as $resolved_status) {
                $statuses[] = new AirRaidAlertStatus(
                    $resolved_status['location_title'],
                    $resolved_status['status'],
                    $resolved_status['uid']
                );
            }

            return new AirRaidAlertStatuses($statuses);
        }, 'air_raid_statuses_all');
    }

    /**
     * Creates an asynchronous API request
     *
     * @template T
     *
     * @param  string  $endpoint  API endpoint
     * @param  bool  $use_cache  Whether to use cached results
     * @param  callable(ResponseInterface): T  $processor  Function to process the response data
     * @param  string  $type  Cache type identifier
     * @param  string  $cache_key_suffix  Optional suffix for the cache key
     * @return PromiseInterface Promise that resolves to the processed result
     */
    private function createAsync(string $endpoint, bool $use_cache, callable $processor, string $type = 'default', string $cache_key_suffix = '') : PromiseInterface
    {
        $cache_key = $endpoint . $cache_key_suffix;
        return $this->cache_manager->getOrSet(
            $cache_key,
            function () use ($endpoint, $processor, $cache_key) {
                $headers = [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept'        => 'application/json',
                    'User-Agent'    => UserAgent::getUserAgent(),
                ];

                $last_modified = $this->cache_manager->getLastModified($cache_key);
                if ($last_modified) {
                    $headers['If-Modified-Since'] = $last_modified;
                }

                return $this->client->requestAsync('GET', $this->base_url . $endpoint, [
                    'headers' => $headers,
                ])->then(
                    function (ResponseInterface $response) use ($cache_key, $processor) {
                        if (304 === $response->getStatusCode()) {
                            return $this->cache_manager->getCachedData($cache_key);
                        }

                        $last_modified = $response->getHeaderLine('Last-Modified');
                        if ($last_modified) {
                            $this->cache_manager->setLastModified($cache_key, $last_modified);
                        }

                        $processed = $processor($response);
                        $this->cache_manager->storeProcessedData($cache_key, $processed);

                        return $processed;
                    },
                    function (\Throwable $e) {
                        if ($e instanceof \Exception) {
                            $this->processError($e);
                        } else {
                            throw new ApiError('Fatal error: ' . $e->getMessage(), $e->getCode(), $e);
                        }
                    }
                );
            },
            $type,
            $use_cache
        );
    }

    /**
     * Resolves a location identifier to a UID
     *
     * @param  string|int  $identifier  Location identifier (name or UID)
     * @return int Resolved location UID
     *
     * @throws InvalidParameterException If the location name cannot be resolved
     */
    private function resolveUid(string|int $identifier) : int
    {
        if (is_string($identifier)) {
            if (ctype_digit($identifier)) {
                return (int) $identifier;
            }

            return (new LocationUidResolver())->resolveUid($identifier);
        }

        return $identifier;
    }

    /**
     * Processes API errors and throws appropriate exceptions
     *
     * @param  \Exception  $error  The caught exception
     * @return void
     *
     * @throws UnauthorizedError For 401 responses
     * @throws ForbiddenError For 403 responses
     * @throws NotFoundError For 404 responses
     * @throws RateLimitError For 429 responses
     * @throws BadRequestError For 400 responses
     * @throws InternalServerError For 500 responses
     * @throws ApiError For other API errors
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
     * Configures cache TTL (Time To Live) settings for different request types
     *
     * @param  array<string, int>  $ttl_config  Associative array of cache types and their TTL in seconds
     * @return void
     */
    public function configureCacheTtl(array $ttl_config) : void
    {
        foreach ($ttl_config as $type => $ttl) {
            $this->cache_manager->setTtl($type, $ttl);
        }
    }

    /**
     * Clears cached items by tag(s)
     *
     * @param  string|string[]  $tags  A single tag or an array of tags to invalidate
     * @return void
     */
    public function clearCache(string|array $tags) : void
    {
        $this->cache_manager->invalidateTags($tags);
    }
}
