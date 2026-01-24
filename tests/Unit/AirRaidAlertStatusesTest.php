<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
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
        $status1 = new AirRaidAlertStatus('', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('', AlertStatus::NO_ALERT, 2);
        $status3 = new AirRaidAlertStatus('', AlertStatus::PARTLY, 3);

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
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $status3 = new AirRaidAlertStatus('Одеська область', AlertStatus::PARTLY, 3);
        $status4 = new AirRaidAlertStatus('Харківська область', AlertStatus::ACTIVE, 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $activeStatuses = $airRaidAlertStatuses->filterByStatus(AlertStatus::ACTIVE);
        $this->assertCount(2, $activeStatuses);
        $this->assertContains($status1, $activeStatuses);
        $this->assertContains($status4, $activeStatuses);

        $noAlertStatuses = $airRaidAlertStatuses->filterByStatus(AlertStatus::NO_ALERT);
        $this->assertCount(1, $noAlertStatuses);
        $this->assertContains($status2, $noAlertStatuses);

        $partlyStatuses = $airRaidAlertStatuses->filterByStatus(AlertStatus::PARTLY);
        $this->assertCount(1, $partlyStatuses);
        $this->assertContains($status3, $partlyStatuses);

        // 'non-existent' will fallback to NO_ALERT in our implementation of fromString
        $nonExistentStatuses = $airRaidAlertStatuses->filterByStatus('non-existent');
        $this->assertCount(1, $nonExistentStatuses);
        $this->assertContains($status2, $nonExistentStatuses);
    }

    public function testGetActiveAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $status3 = new AirRaidAlertStatus('Одеська область', AlertStatus::PARTLY, 3);
        $status4 = new AirRaidAlertStatus('Харківська область', AlertStatus::ACTIVE, 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $activeStatuses = $airRaidAlertStatuses->getActiveAlertStatuses();
        $this->assertCount(2, $activeStatuses);
        $this->assertContains($status1, $activeStatuses);
        $this->assertContains($status4, $activeStatuses);
    }

    public function testGetPartlyActiveAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $status3 = new AirRaidAlertStatus('Одеська область', AlertStatus::PARTLY, 3);
        $status4 = new AirRaidAlertStatus('Харківська область', AlertStatus::ACTIVE, 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $partlyStatuses = $airRaidAlertStatuses->getPartlyActiveAlertStatuses();
        $this->assertCount(1, $partlyStatuses);
        $this->assertContains($status3, $partlyStatuses);
    }

    public function testGetNoAlertStatuses()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $status3 = new AirRaidAlertStatus('Одеська область', AlertStatus::PARTLY, 3);
        $status4 = new AirRaidAlertStatus('Харківська область', AlertStatus::ACTIVE, 4);

        $statuses = [$status1, $status2, $status3, $status4];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $noAlertStatuses = $airRaidAlertStatuses->getNoAlertStatuses();
        $this->assertCount(1, $noAlertStatuses);
        $this->assertContains($status2, $noAlertStatuses);
    }

    public function testGetIterator()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
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
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertCount(2, $airRaidAlertStatuses);
    }

    public function testOffsetExists()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertTrue(isset($airRaidAlertStatuses[0]));
        $this->assertTrue(isset($airRaidAlertStatuses[1]));
        $this->assertFalse(isset($airRaidAlertStatuses[2]));
    }

    public function testOffsetGet()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $this->assertEquals($status1, $airRaidAlertStatuses[0]);
        $this->assertEquals($status2, $airRaidAlertStatuses[1]);
        $this->assertNull($airRaidAlertStatuses[2]);
    }

    public function testOffsetSetAndUnset()
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $statuses = [$status1];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        // Test that offsetSet does not modify the collection (read-only)
        $airRaidAlertStatuses[1] = new AirRaidAlertStatus('Одеська область', AlertStatus::PARTLY, 3);
        $this->assertCount(1, $airRaidAlertStatuses);
        $this->assertNull($airRaidAlertStatuses[1]);

        // Test that offsetUnset does not modify the collection (read-only)
        unset($airRaidAlertStatuses[0]);
        $this->assertCount(1, $airRaidAlertStatuses);
        $this->assertEquals($status1, $airRaidAlertStatuses[0]);
    }

    public function testJsonSerialize() : void
    {
        $status1 = new AirRaidAlertStatus('Київська область', AlertStatus::ACTIVE, 1);
        $status2 = new AirRaidAlertStatus('Львівська область', AlertStatus::NO_ALERT, 2);
        $statuses = [$status1, $status2];
        $airRaidAlertStatuses = new AirRaidAlertStatuses($statuses);

        $expectedJson = json_encode([
            [
                'location_title' => 'Київська область',
                'status' => 'active',
                'uid' => 1,
            ],
            [
                'location_title' => 'Львівська область',
                'status' => 'no_alert',
                'uid' => 2,
            ],
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($airRaidAlertStatuses));
    }

    public function testToStringReturnsEmptyStringOnJsonEncodeFailure()
    {
        $status = new AirRaidAlertStatus('Test', AlertStatus::ACTIVE, 1);
        $statuses = new AirRaidAlertStatuses([$status]);
        
        $reflection = new \ReflectionClass($statuses);
        $property = $reflection->getProperty('statuses');
        $property->setAccessible(true);
        // Insert a resource to cause json_encode failure
        $property->setValue($statuses, [fopen('php://memory', 'r')]);

        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            $this->assertEquals('', (string)$statuses);
        } finally {
            ini_set('error_log', $originalErrorLog);
        }
    }
}
