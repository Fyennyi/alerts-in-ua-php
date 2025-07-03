<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ApiIntegrationTest extends TestCase
{
    private $mockHandler;
    private $client;
    private $token = 'test_api_token';

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->client = new Client(['handler' => $handlerStack]);
    }

    /**
     * This test simulates a full workflow of getting alerts and analyzing them
     */
    public function testAlertWorkflow()
    {
        // Step 1: Get active alerts
        $alertsResponseJson = file_get_contents(__DIR__ . '/../fixtures/active_alerts.json');
        $this->mockHandler->append(new Response(200, [], $alertsResponseJson));
        
        $alertsClient = $this->createMockAlertsClient();
        $alertsFiber = $alertsClient->getActiveAlerts(false);
        $alertsClient->wait();
        $alerts = $alertsFiber->getReturn();
        
        // Analyze alerts
        $this->assertGreaterThan(0, count($alerts->getAllAlerts()));
        $airRaidAlerts = $alerts->getAirRaidAlerts();
        $oblastAlerts = $alerts->getOblastAlerts();
        
        // Step 2: Get alerts history for a specific region
        $historyResponseJson = file_get_contents(__DIR__ . '/../fixtures/alerts_history.json');
        $this->mockHandler->append(new Response(200, [], $historyResponseJson));
        
        $historyFiber = $alertsClient->getAlertsHistory('Харківська область', 'week_ago', false);
        $alertsClient->wait();
        $history = $historyFiber->getReturn();
        
        // Check history
        $this->assertGreaterThan(0, count($history->getAllAlerts()));
        
        // Step 3: Calculate some statistics (example of business logic)
        $oblastStats = [];
        foreach ($history->getAllAlerts() as $alert) {
            $oblast = $alert->location_oblast;
            if (!isset($oblastStats[$oblast])) {
                $oblastStats[$oblast] = 0;
            }
            $oblastStats[$oblast]++;
        }
        
        $this->assertArrayHasKey('Харківська область', $oblastStats);
    }

    private function createMockAlertsClient()
    {
        $alertsClient = new AlertsClient($this->token);
        
        // Inject mock client
        $reflectionClass = new ReflectionClass($alertsClient);
        $clientProperty = $reflectionClass->getProperty('client');
        $clientProperty->setAccessible(true);
        $clientProperty->setValue($alertsClient, $this->client);
        
        return $alertsClient;
    }
}
