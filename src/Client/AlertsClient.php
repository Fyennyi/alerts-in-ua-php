<?php

namespace Fyennyi\AlertsInUa\Client;

use Fyennyi\AlertsInUa\Cache\CacheInterface;
use Fyennyi\AlertsInUa\Cache\InMemoryCache;
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
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use Fyennyi\AlertsInUa\Util\UserAgent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

class AlertsClient
{
    /** @var Client HTTP client for making requests */
    private Client $client;

    /** @var string API authentication token */
    private string $token;

    /** @var string Base URL for the API */
    private string $base_url = 'https://api.alerts.in.ua/v1/';

    /** @var SmartCacheManager Manages caching of API responses */
    private SmartCacheManager $cache_manager;

    /**
     * Constructor for alerts.in.ua API client
     *
     * @param  string  $token  API token
     * @param  CacheInterface|null  $cache  Optional cache implementation
     */
    public function __construct(string $token, ?CacheInterface $cache = null)
    {
        $this->client = new Client;
        $this->token = $token;
        $this->cache_manager = new SmartCacheManager($cache ?? new InMemoryCache());
    }

    /**
     * Retrieves active alerts asynchronously
     *
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an Alerts object
     */
    public function getActiveAlertsAsync(bool $use_cache = false) : PromiseInterface
    {
        return $this->createAsync('alerts/active.json', $use_cache, fn ($data) => new Alerts($data), 'active_alerts');
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

        return $this->createAsync($url, $use_cache, fn ($data) => new Alerts($data), 'alerts_history');
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

        return $this->createAsync(
            $url,
            $use_cache,
            function (array $data) use ($oblast_uid) : AirRaidAlertOblastStatus {
                $location_title = (new LocationUidResolver())->resolveLocationTitle($oblast_uid);
                $first_value = '';
                if (count($data) > 0) {
                    $first_value = reset($data);
                    if (! is_string($first_value)) {
                        $first_value = '';
                    }
                }

                return new AirRaidAlertOblastStatus($location_title, $first_value);
            },
            'air_raid_status'
        );
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
        return $this->createAsync(
            'iot/active_air_raid_alerts_by_oblast.json',
            $use_cache,
            function (array $data) use ($oblast_level_only) : AirRaidAlertOblastStatuses {
                if (empty($data) || ! is_string($data[0] ?? null)) {
                    return new AirRaidAlertOblastStatuses('', $oblast_level_only);
                }

                $status_string = $data[0];
                if (27 !== strlen($status_string)) {
                    $status_string = substr($status_string, 0, 27);
                }

                return new AirRaidAlertOblastStatuses($status_string, $oblast_level_only);
            },
            'air_raid_statuses'
        );
    }

    /**
     * Creates an asynchronous API request
     *
     * @param  string  $endpoint  API endpoint
     * @param  bool  $use_cache  Whether to use cached results
     * @param  callable(array<int|string, mixed>): mixed  $processor  Function to process the response data
     * @param  string  $type  Cache type identifier
     * @return PromiseInterface Promise that resolves to the processed result
     */
    private function createAsync(string $endpoint, bool $use_cache, callable $processor, string $type = 'default') : PromiseInterface
    {
        return $this->cache_manager->getOrSet(
            $endpoint,
            function () use ($endpoint, $processor) {
                $headers = [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept'        => 'application/json',
                    'User-Agent'    => UserAgent::getUserAgent(),
                ];

                $last_modified = $this->cache_manager->getLastModified($endpoint);
                if ($last_modified) {
                    $headers['If-Modified-Since'] = $last_modified;
                }

                return $this->client->requestAsync('GET', $this->base_url . $endpoint, [
                    'headers' => $headers,
                ])->then(
                    function (ResponseInterface $response) use ($endpoint, $processor) {
                        if (304 === $response->getStatusCode()) {
                            return $this->cache_manager->getCachedData($endpoint);
                        }

                        $body = $response->getBody();
                        $data = json_decode($body->getContents(), true);
                        if (! is_array($data)) {
                            throw new ApiError('Invalid JSON response received');
                        }

                        $last_modified = $response->getHeaderLine('Last-Modified');
                        if ($last_modified) {
                            $this->cache_manager->setLastModified($endpoint, $last_modified);
                        }

                        $processed = $processor($data);
                        $this->cache_manager->storeProcessedData($endpoint, $processed);

                        return $processed;
                    },
                    function (\Throwable $e) {
                        if ($e instanceof \Exception) {
                            $this->processError($e);
                        } else {
                            throw new ApiError('Fatal error: ' . $e->getMessage(), $e->getCode(), $e);
                        }
                        throw $e;
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
     * Clears cached items matching a pattern
     *
     * @param  string|null  $pattern  Cache key pattern (null clears all)
     * @return void
     */
    public function clearCache(?string $pattern = null) : void
    {
        $this->cache_manager->invalidatePattern($pattern ?? '*');
    }
}
