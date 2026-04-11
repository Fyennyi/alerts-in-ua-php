<?php

namespace Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Exception\ApiError;
use Fyennyi\AlertsInUa\Model\Alerts;
use PHPUnit\Framework\TestCase;
use React\Http\Browser;
use React\Http\Message\Response;
use React\Promise\Promise;
use ReflectionClass;
use ReflectionMethod;

use function React\Async\await;
use function React\Promise\resolve;

class AlertsClientTest extends TestCase
{
    /** @var Browser|\PHPUnit\Framework\MockObject\MockObject */
    private $mockBrowser;

    private AlertsClient $alertsClient;

    protected function setUp() : void
    {
        // 1. Create the mock browser
        $this->mockBrowser = $this->createMock(Browser::class);

        // 2. Create the real AlertsClient
        $this->alertsClient = new AlertsClient('test-token');

        // 3. Use reflection to inject the mocked Browser client
        $reflectionClass = new ReflectionClass($this->alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($this->alertsClient, $this->mockBrowser);
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
        
        $response = new Response(200, ['Last-Modified' => date('D, d M Y H:i:s T')], $jsonPayload);
        
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve($response));

        // Call the method and wait for the result
        $alerts = await($this->alertsClient->getActiveAlertsAsync());

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
        $response = new Response(200, [], $invalidJsonPayload);
        
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve($response));

        // Expect an ApiError exception
        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        // Call the method
        await($this->alertsClient->getActiveAlertsAsync());
    }

    public function testAlertsHistoryAsyncThrowsExceptionOnInvalidJson()
    {
        // Prepare mock response with invalid JSON
        $invalidJsonPayload = '{"history": [{"id": 1]}}'; // Malformed JSON
        $response = new Response(200, [], $invalidJsonPayload);
        
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve($response));

        // Expect an ApiError exception
        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        // Call the method
        await($this->alertsClient->getAlertsHistoryAsync('м. Київ'));
    }

    public function testAirRaidAlertStatusAsyncThrowsExceptionOnInvalidJson()
    {
        // Prepare mock response with invalid JSON
        $invalidJsonPayload = '{"status": [{"id": 1]}}'; // Malformed JSON
        $response = new Response(200, [], $invalidJsonPayload);
        
        $this->mockBrowser->expects($this->once())
            ->method('request')
            ->willReturn(resolve($response));

        // Expect an ApiError exception
        $this->expectException(ApiError::class);
        $this->expectExceptionMessage('Invalid response received');

        // Call the method
        await($this->alertsClient->getAirRaidAlertStatusAsync('м. Київ'));
    }
}
