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
use ReflectionClass;
use ReflectionMethod;

class AlertsClientTest extends TestCase
{
    private MockHandler $mockHandler;

    private AlertsClient $alertsClient;

    protected function setUp() : void
    {
        // 1. Create the mock handler
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $guzzleClient = new GuzzleClient(['handler' => $handlerStack]);

        // 2. Create the real AlertsClient
        $this->alertsClient = new AlertsClient('test-token');

        // 3. Use reflection to inject the mocked Guzzle client
        $reflectionClass = new ReflectionClass($this->alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->alertsClient, $guzzleClient);
    }

    public function testGetActiveAlertsAsyncSuccessfully()
    {
        // Prepare mock response
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

        // Call the method and wait for the result
        $alerts = $this->alertsClient->getActiveAlertsAsync()->wait();

        // Assert the results
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
        // Prepare mock response with invalid JSON
        $invalidJsonPayload = '{"alerts": [{"id": 1]}}'; // Malformed JSON
        $this->mockHandler->append(new Response(200, [], $invalidJsonPayload));

        // Expect an ApiError exception
        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        // Call the method
        $this->alertsClient->getActiveAlertsAsync()->wait();
    }
}
