<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\Client as GuzzleClient;
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
        $handlerStack->push(Middleware::history($this->historyContainer));
        $this->client = new GuzzleClient(['handler' => $handlerStack]);

        $this->alertsClient = new AlertsClient('test_token');

        $reflectionClass = new ReflectionClass($this->alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->alertsClient, $this->client);
    }

    public function testGetActiveAlerts()
    {
        // Mock response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'alerts' => [[
                'id' => 1,
                'location_title' => 'Київ',
                'location_type' => 'city',
                'started_at' => '2023-01-02T10:15:30.000Z',
                'alert_type' => 'air_raid',
            ]],
            'meta' => ['last_updated_at' => '2023-01-02T11:30:00.000Z'],
        ])));

        // Call method
        $result = $this->alertsClient->getActiveAlertsAsync(false)->wait();

        // Assert request was made correctly
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/alerts/active.json', $request->getUri()->getPath());

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertEquals('Київ', $result->getAllAlerts()[0]->getLocationTitle());
    }

    public function testGetAlertsHistory()
    {
        // Mock response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'alerts' => [[
                'id' => 1,
                'location_title' => 'Харківська область',
                'location_type' => 'oblast',
                'started_at' => '2023-01-01T10:00:00.000Z',
                'finished_at' => '2023-01-01T11:00:00.000Z',
                'alert_type' => 'air_raid',
            ]],
            'meta' => ['last_updated_at' => '2023-01-02T11:30:00.000Z'],
        ])));

        // Call method with location title
        $result = $this->alertsClient->getAlertsHistoryAsync('Харківська область', 'day_ago', false)->wait();

        // Assert request was made correctly
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/regions/22/alerts/day_ago.json', $request->getUri()->getPath());

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertEquals('Харківська область', $result->getAllAlerts()[0]->getLocationTitle());
    }

    public function testGetAirRaidAlertStatus()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([
            "Харківська область" => "air_raid"
        ])));

        $result = $this->alertsClient->getAirRaidAlertStatusAsync(22)->wait();

        $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $result);
        $this->assertEquals("Харківська область", $result->getOblast());
    }

    public function testGetAirRaidAlertStatusesByOblast()
    {
        $testData = ["ANPNAPNNNNNNNNNNNNNNNNNNNNN"];
        $this->mockHandler->append(new Response(200, [], json_encode($testData)));

        $result = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync()->wait();

        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $statuses = $result->getStatuses();
    
        // Basic structure validation
        $this->assertCount(27, $statuses);
        $this->assertEquals('A', $statuses[0]->getStatus());
        $this->assertEquals('Автономна Республіка Крим', $statuses[0]->getOblast());
    }

public function testOblastLevelFilter()
{
    $testData = ["ANPNAPNNNNNNNNNNNNNNNNNNNNN"];
    $this->mockHandler->append(new Response(200, [], json_encode($testData)));

    $result = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync(false)->wait(); // false - щоб отримати всі статуси
    $statuses = $result->getStatuses();

    // Дебаг-вивід усіх областей зі статусами
    echo "\nDebug output - all regions:\n";
    echo str_pad("Region", 30) . " | Status\n";
    echo "------------------------------|--------\n";
    foreach ($statuses as $status) {
        echo str_pad($status->getOblast(), 30) . " | " . $status->getStatus() . "\n";
    }

    // Тестуємо фільтрацію тільки для A (oblast level)
    $filteredResult = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync(true)->wait();
    $filteredStatuses = $filteredResult->getStatuses();

    // Дебаг-вивід відфільтрованих результатів
    echo "\nFiltered results (only A statuses):\n";
    foreach ($filteredStatuses as $status) {
        echo $status->getOblast() . " (" . $status->getStatus() . ")\n";
    }

    // Перевірки
    $this->assertCount(2, $filteredStatuses);
    $this->assertEquals('A', $filteredStatuses[0]->getStatus());
    $this->assertEquals('Автономна Республіка Крим', $filteredStatuses[0]->getOblast());
    $this->assertEquals('A', $filteredStatuses[1]->getStatus());
    $this->assertEquals('Донецька область', $filteredStatuses[1]->getOblast());
}

    public function testGetAirRaidAlertStatusWithEmptyResponse()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $result = $this->alertsClient->getAirRaidAlertStatusAsync(22)->wait();

        $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $result);
        $this->assertEquals("", $result->getStatus());
    }

    public function testGetAirRaidAlertStatusesByOblastWithEmptyResponse()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([])));

        $result = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync()->wait();

        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $this->assertCount(0, $result->getStatuses());
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
        $this->alertsClient->getActiveAlertsAsync(false)->wait(); // Here the UnauthorizedError will be thrown and the test will pass
    }

    public function testCache()
    {
        // Mock response
        $this->mockHandler->append(new Response(200, [], json_encode([
            'alerts' => [[
                'id' => 1,
                'location_title' => 'Київ',
                'alert_type' => 'air_raid',
            ]],
        ])));

        // First call should make a request
        $result1 = $this->alertsClient->getActiveAlertsAsync(true)->wait();

        // Second call with cache should not make a request
        $result2 = $this->alertsClient->getActiveAlertsAsync(true)->wait();

        $this->assertInstanceOf(Alerts::class, $result1);
        $this->assertInstanceOf(Alerts::class, $result2);

        // Assert only one request was made
        $this->assertCount(1, $this->historyContainer);
    }
}
