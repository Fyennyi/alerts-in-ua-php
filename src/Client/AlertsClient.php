<?php

namespace Fyennyi\AlertsInUa\Client;

use Fiber;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Exception\BadRequestError;
use Fyennyi\AlertsInUa\Exception\ForbiddenError;
use Fyennyi\AlertsInUa\Exception\InternalServerError;
use Fyennyi\AlertsInUa\Exception\NotFoundError;
use Fyennyi\AlertsInUa\Exception\RateLimitError;
use Fyennyi\AlertsInUa\Exception\UnauthorizedError;
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use Fyennyi\AlertsInUa\Util\UserAgent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Utils;

class AlertsClient
{
    private Client $client;

    private string $token;

    private string $baseUrl = 'https://api.alerts.in.ua/v1/';

    private array $cache = [];

    private array $promises = [];

    private array $fibers = [];

    /**
     * Constructor for alerts.in.ua API client
     *
     * @param  string  $token  API token
     */
    public function __construct(string $token)
    {
        $this->client = new Client;
        $this->token = $token;
    }

    /**
     * Get active alerts using fibers
     *
     * @param  bool  $use_cache  Use cache
     * @return Fiber Fiber with result
     */
    public function getActiveAlerts(bool $use_cache = true) : Fiber
    {
        return $this->createFiber('alerts/active.json', $use_cache, fn ($data) => new Alerts($data));
    }

    /**
     * Get alert history for specific region using fibers
     *
     * @param  string|int  $oblast_uid_or_location_title  Region unique identifier or name
     * @param  string  $period  Alert history period
     * @param  bool  $use_cache  Use cache
     * @return Fiber Fiber with result
     */
    public function getAlertsHistory(string|int $oblast_uid_or_location_title, string $period = 'week_ago', bool $use_cache = true) : Fiber
    {
        $oblast_uid = $this->resolveUid($oblast_uid_or_location_title);
        $url = "regions/{$oblast_uid}/alerts/{$period}.json";

        return $this->createFiber($url, $use_cache, fn ($data) => new Alerts($data));
    }

    /**
     * Create fiber for API request
     *
     * @param  string  $endpoint  API endpoint
     * @param  bool  $use_cache  Use cache
     * @param  callable  $processor  Response processing function
     * @return Fiber Fiber with result
     */
    private function createFiber(string $endpoint, bool $use_cache, callable $processor) : Fiber
    {
        if ($use_cache && isset($this->cache[$endpoint])) {
            $fiber = new Fiber(fn () => call_user_func($processor, $this->cache[$endpoint]));

            $fiber->start();

            return $fiber;
        }

        $fiber = new Fiber(function () use ($endpoint, $use_cache, $processor) {
            $promise = $this->client->requestAsync('GET', $this->baseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                    'User-Agent' => UserAgent::getUserAgent(),
                ],
            ]);

            $this->promises[] = $promise;
            Fiber::suspend();

            try {
                $response = $promise->wait();
                $data = json_decode($response->getBody()->getContents(), true);

                if ($use_cache) {
                    $this->cache[$endpoint] = $data;
                }

                return call_user_func($processor, $data);
            } catch (\Exception $e) {
                $this->processError($e);
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
     */
    private function resolveUid(string|int $identifier) : int
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
}
