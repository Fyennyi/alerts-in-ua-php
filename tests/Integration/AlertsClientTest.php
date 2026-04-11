<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use React\Http\Browser;
use React\Http\Message\Response;
use React\Http\Message\ResponseException;
use React\Promise\PromiseInterface;
use ReflectionClass;

use function React\Async\await;
use function React\Promise\reject;
use function React\Promise\resolve;

class AlertsClientTest extends TestCase
{
    /** @var Browser|\PHPUnit\Framework\MockObject\MockObject */
    private $mockBrowser;

    private AlertsClient $alertsClient;

    private \Psr\SimpleCache\CacheInterface $cache;

    protected function setUp() : void
    {
        $this->mockBrowser = $this->createMock(Browser::class);

        $this->cache = new \Symfony\Component\Cache\Psr16Cache(
            new \Symfony\Component\Cache\Adapter\TagAwareAdapter(
                new \Symfony\Component\Cache\Adapter\ArrayAdapter()
            )
        );
        $this->alertsClient = new AlertsClient('test_token', $this->cache);
        $this->alertsClient->setRequestInterval(0);

        $reflectionClass = new ReflectionClass($this->alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->alertsClient, $this->mockBrowser);
    }

    public function testGetActiveAlerts()
    {
        // Mock response
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/alerts/active.json')
            ->willReturn(resolve(new Response(200, [], json_encode([
                'alerts' => [[
                    'id' => 1,
                    'location_title' => 'Київ',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T10:15:30.000Z',
                    'alert_type' => 'air_raid',
                ]],
                'meta' => ['last_updated_at' => '2023-01-02T11:30:00.000Z'],
            ]))));

        // Call method
        $result = await($this->alertsClient->getActiveAlertsAsync());

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertEquals('Київ', $result->getAllAlerts()[0]->getLocationTitle());
    }

    public function testGetAlertsHistory()
    {
        // Mock response
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/regions/22/alerts/week_ago.json')
            ->willReturn(resolve(new Response(200, [], json_encode([
                'alerts' => [[
                    'id' => 1,
                    'location_title' => 'Харківська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-01T10:00:00.000Z',
                    'finished_at' => '2023-01-01T11:00:00.000Z',
                    'alert_type' => 'air_raid',
                ]],
                'meta' => ['last_updated_at' => '2023-01-02T11:30:00.000Z'],
            ]))));

        // Call method with location title
        $result = await($this->alertsClient->getAlertsHistoryAsync('Харківська область'));

        // Assert response was parsed correctly
        $this->assertInstanceOf(Alerts::class, $result);
        $this->assertEquals('Харківська область', $result->getAllAlerts()[0]->getLocationTitle());
    }

    public function testGetAirRaidAlertStatus()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts/22.json')
            ->willReturn(resolve(new Response(200, [], json_encode("A"))));

        $result = await($this->alertsClient->getAirRaidAlertStatusAsync(22));

        $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $result);
        $this->assertEquals("Харківська область", $result->getOblast());
        $this->assertEquals(AlertStatus::ACTIVE, $result->getStatus());
    }

    public function testGetAirRaidAlertStatusesByOblast()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts_by_oblast.json')
            ->willReturn(resolve(new Response(200, [], json_encode("ANPNAPNNNNNNNNNNNNNNNNNNNNN"))));

        $result = await($this->alertsClient->getAirRaidAlertStatusesByOblastAsync());

        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $statuses = $result->getStatuses();

        // Basic structure validation
        $this->assertCount(27, $statuses);
        $this->assertEquals(AlertStatus::ACTIVE, $statuses[0]->getStatus());
        $this->assertEquals('Автономна Республіка Крим', $statuses[0]->getOblast());
    }

    public function testOblastLevelFilter()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts_by_oblast.json')
            ->willReturn(resolve(new Response(200, [], json_encode("ANPNAPNNNNNNNNNNNNNNNNNNNNN"))));

        $result = await($this->alertsClient->getAirRaidAlertStatusesByOblastAsync(true));
        $statuses = array_values($result->getActiveAlertOblasts());

        // Should only contain 'active' statuses (2 in test data)
        $this->assertCount(2, $statuses);

        // Check first alert (Autonomous Republic of Crimea)
        $this->assertEquals(AlertStatus::ACTIVE, $statuses[0]->getStatus());
        $this->assertEquals('Автономна Республіка Крим', $statuses[0]->getOblast());

        // Check second alert (Donetsk Oblast)
        $this->assertEquals(AlertStatus::ACTIVE, $statuses[1]->getStatus());
        $this->assertEquals('Донецька область', $statuses[1]->getOblast());
    }

    public function testGetAirRaidAlertStatusWithEmptyResponse()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts/22.json')
            ->willReturn(resolve(new Response(200, [], json_encode(""))));

        $result = await($this->alertsClient->getAirRaidAlertStatusAsync(22));

        $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $result);
        $this->assertEquals(AlertStatus::NO_ALERT, $result->getStatus());
    }

    public function testGetAirRaidAlertStatusesByOblastWithEmptyResponse()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts_by_oblast.json')
            ->willReturn(resolve(new Response(200, [], json_encode([]))));

        $result = await($this->alertsClient->getAirRaidAlertStatusesByOblastAsync());

        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $this->assertCount(0, $result->getStatuses());
    }

    public function testErrorHandling()
    {
        $response = new Response(401, [], json_encode(['error' => 'Invalid token']));
        
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(reject(new ResponseException($response)));

        // Expect exception
        $this->expectException(\Fyennyi\AlertsInUa\Exception\UnauthorizedError::class);

        // Call method
        await($this->alertsClient->getActiveAlertsAsync());
    }

    #[DataProvider('apiErrorProvider')]
    public function testApiErrorHandling(int $statusCode, string $expectedExceptionClass)
    {
        $response = new Response($statusCode, [], json_encode(['error' => 'An error occurred']));

        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(reject(new ResponseException($response)));

        $this->expectException($expectedExceptionClass);

        await($this->alertsClient->getActiveAlertsAsync());
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
        $callCount = 0;
        $this->mockBrowser->method('request')
            ->willReturnCallback(function() use (&$callCount) {
                $callCount++;
                return resolve(new Response(200, [], json_encode([
                    'alerts' => [[
                        'id' => 1,
                        'location_title' => 'Київ',
                        'alert_type' => 'air_raid',
                    ]],
                ])));
            });

        // First call should make a request
        $result1 = await($this->alertsClient->getActiveAlertsAsync(true));
        $this->assertEquals(1, $callCount);

        // Process any background ticks
        \React\EventLoop\Loop::get()->futureTick(function() {});
        \React\EventLoop\Loop::get()->run();

        // Second call with cache should not make a request
        $result2 = await($this->alertsClient->getActiveAlertsAsync(true));

        $this->assertInstanceOf(Alerts::class, $result1);
        $this->assertInstanceOf(Alerts::class, $result2);
        $this->assertEquals(1, $callCount, 'Network request should only be made once when caching is enabled');
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

        $this->mockBrowser->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function($method, $url, $headers) use ($lastModified, $responseBody) {
                if ($url === 'https://api.alerts.in.ua/v1/alerts/active.json') {
                    if (!isset($headers['If-Modified-Since'])) {
                        return resolve(new Response(200, ['Last-Modified' => $lastModified], json_encode($responseBody)));
                    }
                    
                    $this->assertEquals($lastModified, $headers['If-Modified-Since']);
                    return resolve(new Response(304, []));
                }
                return reject(new \Exception('Unexpected URL: ' . $url));
            });

        // First call — caches everything (including processed data)
        $first = await($this->alertsClient->getActiveAlertsAsync());
        $this->assertInstanceOf(Alerts::class, $first);
        $this->assertEquals('Одеська область', $first->getAllAlerts()[0]->getLocationTitle());

        // Second call — should use cached processed data, no new full data fetched
        $second = await($this->alertsClient->getActiveAlertsAsync());
        $this->assertInstanceOf(Alerts::class, $second);
        $this->assertEquals('Одеська область', $second->getAllAlerts()[0]->getLocationTitle());
    }

    public function test304HandlingWithMissingCacheThrowsError()
    {
        $lastModified = 'Sat, 15 Jun 2024 15:16:00 GMT';

        $this->cache->set('alerts/active.json.last_modified', $lastModified);

        // Simulate 304 response
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve(new Response(304, [])));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Received 304 Not Modified but no cached data found.');

        await($this->alertsClient->getActiveAlertsAsync());
    }

    public function test304HandlingWithRawCacheData()
    {
        $lastModified = 'Sat, 15 Jun 2024 15:16:00 GMT';
        $rawData = 'some raw string data';

        $this->cache->set('alerts/active.json.last_modified', $lastModified);
        $this->cache->set('alerts/active.json', $rawData);

        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve(new Response(304, [])));

        $result = await($this->alertsClient->getActiveAlertsAsync());

        $this->assertEquals($rawData, $result);
    }

    public function testClearCacheCallsInvalidateTags()
    {
        $mockCache = $this->createMock(TagAwarePsr16Cache::class);

        $mockCache->expects($this->once())
            ->method('invalidateTags')
            ->with(['test-tag']);

        $client = new AlertsClient('test_token', $mockCache);
        $client->clearCache('test-tag');
    }

    public function testClearCacheCallsInvalidateTagsWithArray()
    {
        $mockCache = $this->createMock(TagAwarePsr16Cache::class);

        $mockCache->expects($this->once())
            ->method('invalidateTags')
            ->with(['tag1', 'tag2']);

        $client = new AlertsClient('test_token', $mockCache);
        $client->clearCache(['tag1', 'tag2']);
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

        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve(new Response(200, [], json_encode(['alerts' => []]))));

        await($this->alertsClient->getActiveAlertsAsync(true));

        $this->alertsClient->clearCache('alerts/active.json');

        $this->assertTrue(true);
    }

    public function testInvalidJsonResponse()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve(new Response(200, [], 'not a valid json')));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        await($this->alertsClient->getActiveAlertsAsync());
    }

    public function testNonRequestExceptionHandling()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(reject(new \Exception('Generic error')));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Request failed: Generic error');

        await($this->alertsClient->getActiveAlertsAsync());
    }

    public function testProcessErrorWithNoResponse()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(reject(new \Exception('Connection error')));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Request failed: Connection error');

        await($this->alertsClient->getActiveAlertsAsync());
    }

    public function testAirRaidStatusesWithLongString()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve(new Response(200, [], json_encode(str_repeat('A', 30)))));
        $result = await($this->alertsClient->getAirRaidAlertStatusesByOblastAsync());
        $this->assertInstanceOf(AirRaidAlertOblastStatuses::class, $result);
        $this->assertCount(27, $result->getStatuses());
    }

    public function testThrowableErrorHandling()
    {
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(reject(new \TypeError('A throwable error')));

        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Request failed: A throwable error');

        await($this->alertsClient->getActiveAlertsAsync());
    }

}

interface TagAwarePsr16Cache extends \Psr\SimpleCache\CacheInterface { public function invalidateTags(array $tags): void; }
