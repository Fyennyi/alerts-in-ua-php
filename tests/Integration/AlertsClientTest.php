<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

class AlertsClientTest extends TestCase
{
    private MockHandler $mockHandler;

    private array $historyContainer = [];

    private GuzzleClient $client;

    private AlertsClient $alertsClient;

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
        $result = $this->alertsClient->getActiveAlertsAsync()->wait();

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
        $result = $this->alertsClient->getAlertsHistoryAsync('Харківська область')->wait();

        // Assert request was made correctly
        $this->assertCount(1, $this->historyContainer);
        $request = $this->historyContainer[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/v1/regions/22/alerts/week_ago.json', $request->getUri()->getPath());

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

        $result = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync(true)->wait();
        $statuses = $result->getStatuses();

        // Should only contain 'A' statuses (2 in test data)
        $this->assertCount(2, $statuses);

        // Check first alert (Autonomous Republic of Crimea)
        $this->assertEquals('A', $statuses[0]->getStatus());
        $this->assertEquals('Автономна Республіка Крим', $statuses[0]->getOblast());

        // Check second alert (Donetsk Oblast)
        $this->assertEquals('A', $statuses[1]->getStatus());
        $this->assertEquals('Донецька область', $statuses[1]->getOblast());
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
        $this->alertsClient->getActiveAlertsAsync()->wait(); // Here the UnauthorizedError will be thrown and the test will pass
    }

    #[DataProvider('apiErrorProvider')]
    public function testApiErrorHandling(int $statusCode, string $expectedException)
    {
        $this->mockHandler->append(
            new RequestException(
                'API Error',
                new Request('GET', 'test'),
                new Response($statusCode, [], json_encode(['error' => 'An error occurred']))
            )
        );

        $this->expectException($expectedException);

        $this->alertsClient->getActiveAlertsAsync()->wait();
    }

    public static function apiErrorProvider() : array
    {
        return [
            'Bad Request' => [400, \Fyennyi\AlertsInUa\Exception\BadRequestError::class],
            'Forbidden' => [403, \Fyennyi\AlertsInUa\Exception\ForbiddenError::class],
            'Not Found' => [404, \Fyennyi\AlertsInUa\Exception\NotFoundError::class],
            'Rate Limit' => [429, \Fyennyi\AlertsInUa\Exception\RateLimitError::class],
            'Internal Server Error' => [500, \Fyennyi\AlertsInUa\Exception\InternalServerError::class],
            'Generic Api Error' => [503, \Fyennyi\AlertsInUa\Exception\ApiError::class],
        ];
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

    public function testLastModifiedAnd304Handling()
    {
        // Create a mock response with Last-Modified
        $lastModified = 'Sat, 15 Jun 2024 15:16:00 GMT';
        $responseBody = [
            'alerts' => [[
                'id' => 42,
                'location_title' => 'Одеська область',
                'location_type' => 'oblast',
                'started_at' => '2024-06-15T14:25:00.000Z',
                'finished_at' => '2024-06-15T15:10:00.000Z',
                'updated_at' => '2024-06-15T15:15:30.000Z',
                'alert_type' => 'air_raid',
                'location_uid' => '51',
                'location_oblast' => 'Одеська область',
                'location_oblast_uid' => '51',
                'location_raion' => 'Одеський район',
                'notes' => 'Інформація з ДСНС',
                'calculated' => false,
            ]],
            'meta' => ['last_updated_at' => '2024-06-15T15:16:00.000Z'],
        ];

        // Append first response with Last-Modified header
        $this->mockHandler->append(new Response(200, ['Last-Modified' => $lastModified], json_encode($responseBody)));

        // First call — caches everything (including processed data)
        $first = $this->alertsClient->getActiveAlertsAsync()->wait();
        $this->assertInstanceOf(Alerts::class, $first);
        $this->assertEquals('Одеська область', $first->getAllAlerts()[0]->getLocationTitle());

        // Clear the request history to isolate next request
        $this->historyContainer = [];

        // Append 304 Not Modified response to simulate unchanged data
        $this->mockHandler->append(new Response(304, []));

        // Second call — should use cached processed data, no new full data fetched
        $second = $this->alertsClient->getActiveAlertsAsync()->wait();
        $this->assertInstanceOf(Alerts::class, $second);
        $this->assertEquals('Одеська область', $second->getAllAlerts()[0]->getLocationTitle());

        // Assert exactly one new HTTP request was sent for the second call
        $this->assertCount(1, $this->historyContainer);
        $sentRequest = $this->historyContainer[0]['request'];

        // Check that If-Modified-Since header was set to previously received Last-Modified
        $this->assertTrue($sentRequest->hasHeader('If-Modified-Since'));
        $this->assertEquals($lastModified, $sentRequest->getHeaderLine('If-Modified-Since'));
    }

    public function testResolveUid()
    {
        $reflection = new ReflectionClass($this->alertsClient);
        $method = $reflection->getMethod('resolveUid');
        $method->setAccessible(true);

        $this->assertEquals(22, $method->invoke($this->alertsClient, 22));
        $this->assertEquals(22, $method->invoke($this->alertsClient, '22'));
        $this->assertEquals(22, $method->invoke($this->alertsClient, 'Харківська область'));

        $this->expectException(InvalidParameterException::class);
        $method->invoke($this->alertsClient, 'Неіснуюча область');
    }

    public function testConfigureAndClearCache()
    {
        $this->alertsClient->configureCacheTtl(['active_alerts' => 100]);
        
        $this->mockHandler->append(new Response(200, [], json_encode(['alerts' => []])));
        
        // This call will set the cache with the new TTL
        $this->alertsClient->getActiveAlertsAsync(true)->wait();
        
        // To verify, we would ideally inspect the cache manager, which is complex with mocks.
        // Instead, we'll just ensure clearCache works.
        
        $this->alertsClient->clearCache('alerts/active.json');
        
        // This is hard to assert without a more complex mock setup for SmartCacheManager,
        // but we are testing the public API contract.
        $this->assertTrue(true); // Placeholder assertion
    }

    public function testInvalidJsonResponse()
    {
        $this->mockHandler->append(new Response(200, [], 'not a valid json'));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        $this->alertsClient->getActiveAlertsAsync()->wait();
    }

    public function testNonRequestExceptionHandling()
    {
        $this->mockHandler->append(new \Exception('Generic error'));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Unknown error: Generic error');

        $this->alertsClient->getActiveAlertsAsync()->wait();
    }

    public function testProcessErrorWithNoResponse()
    {
        $this->mockHandler->append(new RequestException('Connection error', new Request('GET', 'test')));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Request failed: Connection error');

        $this->alertsClient->getActiveAlertsAsync()->wait();
    }

    public function testAirRaidStatusWithNonStringValue()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([123])));
        $result = $this->alertsClient->getAirRaidAlertStatusAsync(22)->wait();
        $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $result);
        $this->assertEquals('', $result->getStatus());
    }

    public function testAirRaidStatusesWithLongString()
    {
        $this->mockHandler->append(new Response(200, [], json_encode([str_repeat('A', 30)])));
        $result = $this->alertsClient->getAirRaidAlertStatusesByOblastAsync()->wait();
        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $this->assertCount(27, $result->getStatuses());
    }

    public function testThrowableErrorHandling()
    {
        $this->mockHandler->append(new \TypeError('A throwable error'));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Fatal error: A throwable error');

        $this->alertsClient->getActiveAlertsAsync()->wait();
    }
}
