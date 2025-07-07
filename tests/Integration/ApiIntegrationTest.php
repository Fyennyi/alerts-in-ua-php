<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Alerts;
use GuzzleHttp\Client as GuzzleClient;
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

    protected function setUp() : void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $this->client = new GuzzleClient(['handler' => $handlerStack]);
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
        $alerts = $alertsClient->getActiveAlertsAsync(false)->wait();

        // Analyze alerts
        $this->assertGreaterThan(0, count($alerts->getAllAlerts()));
        $airRaidAlerts = $alerts->getAirRaidAlerts();
        $oblastAlerts = $alerts->getOblastAlerts();

        // Step 2: Get alerts history
        $historyResponseJson = file_get_contents(__DIR__ . '/../fixtures/alerts_history.json');
        $this->mockHandler->append(new Response(200, [], $historyResponseJson));

        $history = $alertsClient->getAlertsHistoryAsync('Харківська область', 'week_ago', false)->wait();

        // Check history
        $this->assertGreaterThan(0, count($history->getAllAlerts()));

        // Step 3: Analyze history
        $oblastStats = [];
        foreach ($history->getAllAlerts() as $alert) {
            $oblast = $alert->getLocationOblast();
            $oblastStats[$oblast] = ($oblastStats[$oblast] ?? 0) + 1;
        }

        $this->assertArrayHasKey('Харківська область', $oblastStats);
    }

    private function createMockAlertsClient() : AlertsClient
    {
        $alertsClient = new AlertsClient($this->token);

        // Inject mock client
        $reflection = new ReflectionClass($alertsClient);
        $property = $reflection->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($alertsClient, $this->client);

        return $alertsClient;
    }
}
