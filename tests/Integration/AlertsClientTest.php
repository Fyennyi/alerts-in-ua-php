<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AlertsClientTest extends TestCase
{
    private $mockHandler;

    private $historyContainer = [];

    private $client;

    private $alertsClient;

    protected function setUp() : void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);

        // Add history middleware
        $history = Middleware::history($this->historyContainer);
        $handlerStack->push($history);

        $this->client = new Client(['handler' => $handlerStack]);

        // Create alerts client with mock GuzzleHttp client
        $this->alertsClient = new AlertsClient('test_token');

        // Set the GuzzleHttp client via reflection
        $reflectionClass = new ReflectionClass($this->alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->alertsClient, $this->client);
    }

    public function testGetActiveAlerts()
    {
        // Mock response
        $responseBody = json_encode([
            'alerts' => [
                [
                    'id' => 1,
                    'location_title' => 'Київ',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T10:15:30.000Z',
                    'alert_type' => 'air_raid',
                ]
            ],
            'meta' => [
                'last_updated_at' => '2023-01-02T11:30:00.000Z'
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $responseBody));

        // Call method
        $fiber = $this->alertsClient->getActiveAlerts(false);
        $this->alertsClient->wait();
        $result = $fiber->getReturn();

        // Assert request was made correctly
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/alerts/active.json', $request->getUri()->getPath());
        $this->assertEquals('Bearer test_token', $request->getHeader('Authorization')[0]);

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertCount(1, $result->getAllAlerts());
        $alert = $result->getAllAlerts()[0];
        $this->assertEquals('Київ', $alert->location_title);
        $this->assertEquals('air_raid', $alert->alert_type);
    }

    public function testGetAlertsHistory()
    {
        // Mock response
        $responseBody = json_encode([
            'alerts' => [
                [
                    'id' => 1,
                    'location_title' => 'Харківська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-01T10:00:00.000Z',
                    'finished_at' => '2023-01-01T11:00:00.000Z',
                    'alert_type' => 'air_raid',
                ]
            ],
            'meta' => [
                'last_updated_at' => '2023-01-02T11:30:00.000Z'
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $responseBody));

        // Call method with location title
        $fiber = $this->alertsClient->getAlertsHistory('Харківська область', 'day_ago', false);
        $this->alertsClient->wait();
        $result = $fiber->getReturn();

        // Assert request was made correctly
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/regions/22/alerts/day_ago.json', $request->getUri()->getPath());

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertCount(1, $result->getAllAlerts());
        $alert = $result->getAllAlerts()[0];
        $this->assertEquals('Харківська область', $alert->location_title);
    }

    public function testErrorHandling()
    {
        // Mock unauthorized response
        $this->mockHandler->append(
            new RequestException(
                'Unauthorized',
                new Request('GET', 'test'),
                new Response(401, [], json_encode(['error' => 'Invalid token']))
            )
        );

        // Expect exception
        $this->expectException(\Fyennyi\AlertsInUa\Exception\UnauthorizedError::class);

        // Call method
        $fiber = $this->alertsClient->getActiveAlerts(false);
        $this->alertsClient->wait();
        $fiber->getReturn(); // here the UnauthorizedError will be thrown and the test will pass
    }

    public function testCache()
    {
        // Mock response
        $responseBody = json_encode([
            'alerts' => [
                [
                    'id' => 1,
                    'location_title' => 'Київ',
                    'alert_type' => 'air_raid',
                ]
            ],
        ]);

        $this->mockHandler->append(new Response(200, [], $responseBody));

        // First call should make a request
        $fiber1 = $this->alertsClient->getActiveAlerts(true);
        $this->alertsClient->wait();
        $result1 = $fiber1->getReturn();

        // Second call with cache should not make a request
        $fiber2 = $this->alertsClient->getActiveAlerts(true);
        $this->alertsClient->wait();
        $result2 = $fiber2->getReturn();

        // Assert only one request was made
        $this->assertCount(1, $this->historyContainer);
    }
}
