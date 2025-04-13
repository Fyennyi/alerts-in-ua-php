<?php

namespace AlertsUA;

use Fiber;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use GuzzleHttp\Exception\RequestException;

class AlertsClient
{
    private $client;

    private $token;

    private $baseUrl = 'https://api.alerts.in.ua/v1/';

    private $cache = [];

    private $promises = [];

    private $fibers = [];

    /**
     * Constructor for alerts.in.ua API client
     *
     * @param  string  $token  API token
     */
    public function __construct($token)
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
    public function getActiveAlerts($use_cache = true)
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
    public function getAlertsHistory($oblast_uid_or_location_title, $period = 'week_ago', $use_cache = true)
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
    private function createFiber($endpoint, $use_cache, callable $processor)
    {
        $fiber = new Fiber(function () use ($endpoint, $use_cache, $processor) {
            if ($use_cache && isset($this->cache[$endpoint])) {
                return call_user_func($processor, $this->cache[$endpoint]);
            }

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
                throw $this->processError($e);
            }
        });

        $fiber->start();

        $this->fibers[] = $fiber;

        return $fiber;
    }

    /**
     * Wait for all async requests to complete
     */
    public function wait()
    {
        if (empty($this->promises)) {
            return;
        }

        $results = Utils::settle($this->promises)->wait();
        $this->promises = [];

        foreach ($this->fibers as $fiber) {
            if (! $fiber->isTerminated() && $fiber->isSuspended()) {
                $fiber->resume();
            }
        }

        $this->fibers = [];
    }

    /**
     * Process API errors
     *
     * @param  \Exception  $error  Request error
     * @return ApiError Appropriate API error
     */
    private function processError($error)
    {
        if ($error instanceof RequestException) {
            $response = $error->getResponse();
            if ($response) {
                $code = $response->getStatusCode();
                switch ($code) {
                    case 401:
                        return new UnauthorizedError('Unauthorized access. Check your API token.');
                    case 403:
                        return new ForbiddenError('Access forbidden.');
                    case 404:
                        return new NotFoundError('Resource not found.');
                    case 429:
                        return new RateLimitError('Rate limit exceeded.');
                    case 400:
                        return new BadRequestError('Bad request parameters.');
                    default:
                        return new ApiError('API error: ' . $error->getMessage());
                }
            } else {
                return new ApiError('Request failed: ' . $error->getMessage());
            }
        } else {
            return new ApiError('Unknown error: ' . $error->getMessage());
        }
    }

    /**
     * Resolve UID by location name
     *
     * @param  string|int  $identifier  Identifier
     * @return int Location UID
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
}
