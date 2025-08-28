<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\AirRaidAlertStatuses;
use Fyennyi\AlertsInUa\Model\AirRaidAlertStatus;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AirRaidAlertStatusesTest extends TestCase
{
    public function testConstructorAndUidCachePopulation()
    {
        $status1 = new AirRaidAlertStatus('', 'active', 1);
        $status2 = new AirRaidAlertStatus('', 'no_alert', 2);
        $status3 = new AirRaidAlertStatus('', 'partly', 3);

        $statuses = [$status1, $status2, $status3];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertEquals($status1, $airRaidAlertStatuses->getStatus(1));
        $this->assertEquals($status2, $airRaidAlertStatuses->getStatus(2));
        $this->assertEquals($status3, $airRaidAlertStatuses->getStatus(3));
        $this->assertNull($airRaidAlertStatuses->getStatus(999)); // Test non-existent UID
    }

    public function testGetAirRaidAlertStatusesAsync()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, [], 'NNNPNNNNNPNANANNNNNNNNNNANNNNNNNNNNNNNANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNANNNNNNNNNAAAANNNNNNNNNNNNNAAAAAAANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAAAAAAAANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAAAAAAANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAAAAAAAAAAAAAAAAAAAAAAAAAANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAANNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAAAAAAAAAAAAA'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new GuzzleClient(['handler' => $handlerStack]);

        $alertsClient = new AlertsClient('test_token', null, $client);
        $statuses = $alertsClient->getAirRaidAlertStatusesAsync()->wait();

        $this->assertInstanceOf(AirRaidAlertStatuses::class, $statuses);
    }

    public function testFilterByStatus()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $status3 = new AirRaidAlertStatus('Одеська область', 'partly', 3);
        $status4 = new AirRaidAlertStatus('Харківська область', 'active', 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $activeStatuses = $airRaidAlertStatuses->filterByStatus('active');
        $this->assertCount(2, $activeStatuses);
        $this->assertContains($status1, $activeStatuses);
        $this->assertContains($status4, $activeStatuses);

        $noAlertStatuses = $airRaidAlertStatuses->filterByStatus('no_alert');
        $this->assertCount(1, $noAlertStatuses);
        $this->assertContains($status2, $noAlertStatuses);

        $partlyStatuses = $airRaidAlertStatuses->filterByStatus('partly');
        $this->assertCount(1, $partlyStatuses);
        $this->assertContains($status3, $partlyStatuses);

        $nonExistentStatuses = $airRaidAlertStatuses->filterByStatus('unknown');
        $this->assertCount(0, $nonExistentStatuses);
    }

    public function testGetActiveAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $status3 = new AirRaidAlertStatus('Одеська область', 'partly', 3);
        $status4 = new AirRaidAlertStatus('Харківська область', 'active', 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $activeStatuses = $airRaidAlertStatuses->getActiveAlertStatuses();
        $this->assertCount(2, $activeStatuses);
        $this->assertContains($status1, $activeStatuses);
        $this->assertContains($status4, $activeStatuses);
    }

    public function testGetPartlyActiveAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $status3 = new AirRaidAlertStatus('Одеська область', 'partly', 3);
        $status4 = new AirRaidAlertStatus('Харківська область', 'active', 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $partlyStatuses = $airRaidAlertStatuses->getPartlyActiveAlertStatuses();
        $this->assertCount(1, $partlyStatuses);
        $this->assertContains($status3, $partlyStatuses);
    }

    public function testGetNoAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $status3 = new AirRaidAlertStatus('Одеська область', 'partly', 3);
        $status4 = new AirRaidAlertStatus('Харківська область', 'active', 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $noAlertStatuses = $airRaidAlertStatuses->getNoAlertStatuses();
        $this->assertCount(1, $noAlertStatuses);
        $this->assertContains($status2, $noAlertStatuses);
    }

    public function testGetIterator()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $iteratedStatuses = [];
        foreach ($airRaidAlertStatuses as $status) {
            $iteratedStatuses[] = $status;
        }

        $this->assertEquals($statuses, $iteratedStatuses);
    }

    public function testCount()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertCount(2, $airRaidAlertStatuses);
    }

    public function testOffsetExists()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertTrue(isset($airRaidAlertStatuses[0]));
        $this->assertTrue(isset($airRaidAlertStatuses[1]));
        $this->assertFalse(isset($airRaidAlertStatuses[2]));
    }

    public function testOffsetGet()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $status2 = new AirRaidAlertStatus('Львівська область', 'no_alert', 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertEquals($status1, $airRaidAlertStatuses[0]);
        $this->assertEquals($status2, $airRaidAlertStatuses[1]);
        $this->assertNull($airRaidAlertStatuses[2]);
    }

    public function testOffsetSetAndUnset()
    {
        $status1 = new AirRaidAlertStatus('Київська область', 'active', 1);
        $statuses = [$status1];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        // Test that offsetSet does not modify the collection (read-only)
        $airRaidAlertStatuses[1] = new AirRaidAlertStatus('Одеська область', 'partly', 3);
        $this->assertCount(1, $airRaidAlertStatuses);
        $this->assertNull($airRaidAlertStatuses[1]);

        // Test that offsetUnset does not modify the collection (read-only)
        unset($airRaidAlertStatuses[0]);
        $this->assertCount(1, $airRaidAlertStatuses);
        $this->assertEquals($status1, $airRaidAlertStatuses[0]);
    }
}
