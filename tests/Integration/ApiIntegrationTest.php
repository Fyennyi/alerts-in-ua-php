<?php

namespace Tests\Integration;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Alerts;
use PHPUnit\Framework\TestCase;
use React\Http\Browser;
use React\Http\Message\Response;
use ReflectionClass;

use function React\Async\await;
use function React\Promise\reject;
use function React\Promise\resolve;

class ApiIntegrationTest extends TestCase
{
    /** @var Browser|\PHPUnit\Framework\MockObject\MockObject */
    private $mockBrowser;

    private $token = 'test_api_token';

    protected function setUp() : void
    {
        $this->mockBrowser = $this->createMock(Browser::class);
    }

    /**
     * This test simulates a full workflow of getting alerts and analyzing them
     */
    public function testAlertWorkflow()
    {
        // Step 1: Get active alerts
        $alertsResponseJson = file_get_contents(__DIR__ . '/../fixtures/active_alerts.json');
        $historyResponseJson = file_get_contents(__DIR__ . '/../fixtures/alerts_history.json');
        
        $this->mockBrowser->expects($this->atLeastOnce())
            ->method('request')
            ->willReturnCallback(function($method, $url, $headers = []) use ($alertsResponseJson, $historyResponseJson) {
                if (str_contains($url, 'alerts/active.json')) {
                    return resolve(new Response(200, [], $alertsResponseJson));
                }
                if (str_contains($url, 'regions/22/alerts/week_ago.json')) {
                    return resolve(new Response(200, [], $historyResponseJson));
                }
                return reject(new \Exception('Unexpected URL: ' . $url));
            });

        $alertsClient = $this->createMockAlertsClient();
        $alerts = await($alertsClient->getActiveAlertsAsync());

        // Analyze alerts
        $this->assertGreaterThan(0, count($alerts->getAllAlerts()));
        $airRaidAlerts = $alerts->getAirRaidAlerts();
        $oblastAlerts = $alerts->getOblastAlerts();

        // Step 2: Get alerts history
        $history = await($alertsClient->getAlertsHistoryAsync('Харківська область', 'week_ago'));

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
        $property->setValue($alertsClient, $this->mockBrowser);

        return $alertsClient;
    }
}
