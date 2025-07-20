<?php

namespace Fyennyi\AlertsInUa\Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AlertsClientTest extends TestCase
{
    private MockHandler $mockHandler;

    private AlertsClient $alertsClient;

    protected function setUp() : void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        $this->alertsClient = new AlertsClient('test-token', null, $guzzleClient);
    }

    public function testGetActiveAlertsAsyncSuccessfully()
    {
        // 1. Prepare mock response
        $jsonPayload = '{
            "alerts": [
                {
                    "id": 1,
                    "location_title": "м. Київ",
                    "alert_type": "air_raid"
                }
            ]
        }';
        $this->mockHandler->append(new Response(200, ['Last-Modified' => date('D, d M Y H:i:s T')], $jsonPayload));

        // 2. Call the method and wait for the result
        $alerts = $this->alertsClient->getActiveAlertsAsync()->wait();

        // 3. Assert the results
        $this->assertInstanceOf(Alerts::class, $alerts);
        $this->assertCount(1, $alerts->getAllAlerts());
        $this->assertEquals('м. Київ', $alerts->getAllAlerts()[0]->getLocationTitle());
    }

    public function testResolveUidWithStringDigit()
    {
        // Use reflection to make the private method accessible
        $method = new ReflectionMethod(AlertsClient::class, 'resolveUid');
        $method->setAccessible(true);

        // Call the private method with a string digit
        $result = $method->invoke($this->alertsClient, '22');

        // Assert the result is the correct integer
        $this->assertSame(22, $result);
    }

    public function testApiReturnsInvalidJson()
    {
        // 1. Prepare mock response with invalid JSON
        $invalidJsonPayload = '{"alerts": [{"id": 1]}}'; // Malformed JSON
        $this->mockHandler->append(new Response(200, [], $invalidJsonPayload));

        // 2. Expect an ApiError exception
        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        // 3. Call the method
        $this->alertsClient->getActiveAlertsAsync()->wait();
    }
}
