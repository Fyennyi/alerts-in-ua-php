<?php

namespace Fyennyi\AlertsInUa\Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class AlertsClientTest extends TestCase
{
    public function testGetActiveAlertsAsyncSuccessfully()
    {
        // 1. Prepare mock data and objects
        $jsonPayload = '{
            "alerts": [
                {
                    "id": 1,
                    "location_title": "м. Київ",
                    "alert_type": "air_raid"
                }
            ]
        }';

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn($jsonPayload);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn($mockStream);
        $mockResponse->method('getHeaderLine')->with('Last-Modified')->willReturn(date('D, d M Y H:i:s T'));

        $mockPromise = $this->createMock(PromiseInterface::class);
        // Configure then() to immediately call the first argument (onFulfilled)
        $mockPromise->method('then')->willReturnCallback(function ($onFulfilled) use ($mockResponse) {
            // Create a new mock promise that will return the processed result
            $finalPromise = $this->createMock(PromiseInterface::class);
            $result = $onFulfilled($mockResponse);
            $finalPromise->method('wait')->willReturn($result);

            return $finalPromise;
        });

        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('requestAsync')->willReturn($mockPromise);

        // 2. Create an instance of AlertsClient with the mock client
        $alertsClient = new AlertsClient('test-token', null, $mockClient);

        // 3. Call the method being tested
        $resultPromise = $alertsClient->getActiveAlertsAsync();
        $alerts = $resultPromise->wait();

        // 4. Assert the results
        $this->assertInstanceOf(Alerts::class, $alerts);
        $this->assertCount(1, $alerts->getAllAlerts());
        $this->assertEquals('м. Київ', $alerts->getAllAlerts()[0]->getLocationTitle());
    }

    public function testResolveUidWithStringDigit()
    {
        // 1. Create an instance of AlertsClient
        $alertsClient = new AlertsClient('test-token');

        // 2. Use reflection to make the private method accessible
        $reflection = new \ReflectionClass(AlertsClient::class);
        $method = $reflection->getMethod('resolveUid');
        $method->setAccessible(true);

        // 3. Call the private method with a string digit
        $result = $method->invoke($alertsClient, '22');

        // 4. Assert the result is the correct integer
        $this->assertSame(22, $result);
    }

    public function testApiReturnsInvalidJson()
    {
        // 1. Prepare mock data with invalid JSON
        $invalidJsonPayload = '{"alerts": [{"id": 1]}}'; // Malformed JSON

        $mockStream = $this->createMock(StreamInterface::class);
        $mockStream->method('getContents')->willReturn($invalidJsonPayload);

        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn($mockStream);

        $mockPromise = $this->createMock(PromiseInterface::class);
        $mockPromise->method('then')->willReturnCallback(function ($onFulfilled) use ($mockResponse) {
            $finalPromise = $this->createMock(PromiseInterface::class);
            $result = $onFulfilled($mockResponse);
            $finalPromise->method('wait')->willReturn($result);
            return $finalPromise;
        });

        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->method('requestAsync')->willReturn($mockPromise);

        // 2. Expect an ApiError exception
        $this->expectException(\Fyennyi\AlertsInUa\Exception\ApiError::class);
        $this->expectExceptionMessage('Invalid JSON response received');

        // 3. Create client and call the method
        $alertsClient = new AlertsClient('test-token', null, $mockClient);
        $alertsClient->getActiveAlertsAsync()->wait();
    }
}
