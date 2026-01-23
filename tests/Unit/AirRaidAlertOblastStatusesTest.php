<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use PHPUnit\Framework\TestCase;

class AirRaidAlertOblastStatusesTest extends TestCase
{
    public function testStatusesWithOblastLevelOnly()
    {
        $data = 'ANBCAP'; // Simulated status data for 6 oblasts (A: active, N: no_alert, P: partly)
        $statuses = new AirRaidAlertOblastStatuses($data, true);

        // When oblast_level_only is true, 'P' (partly) becomes 'no_alert'.
        // All 6 statuses should be included, but their resolved status will be 'active' or 'no_alert'.
        $this->assertCount(6, $statuses->getStatuses());

        $expectedStatuses = ['active', 'no_alert', 'no_alert', 'no_alert', 'active', 'no_alert'];
        foreach ($statuses->getStatuses() as $index => $status) {
            $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $status);
            $this->assertEquals($expectedStatuses[$index], $status->getStatus());
        }
    }

    public function testStatusesWithAllLevels()
    {
        $data = 'ANBCAD'; // Simulated status data for 6 oblasts
        $statuses = new AirRaidAlertOblastStatuses($data, false);

        // All statuses should be included
        $this->assertCount(6, $statuses->getStatuses());
        $this->assertCount(6, $statuses); // Explicitly test count()
    }

    public function testWithEmptyStatusString()
    {
        $statuses = new AirRaidAlertOblastStatuses('', false);
        $this->assertCount(0, $statuses->getStatuses());
    }

    public function testWithLongerStatusString()
    {
        // 27 is the number of oblasts
        $data = str_repeat('A', 30);
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $this->assertCount(27, $statuses->getStatuses());
    }

    public function testToString()
    {
        $data = 'ANP'; // A: active, N: no_alert, P: partly
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $expectedJson = json_encode([
            ['oblast' => 'Автономна Республіка Крим', 'status' => 'active'],
            ['oblast' => 'Волинська область', 'status' => 'no_alert'],
            ['oblast' => 'Вінницька область', 'status' => 'partly'],
        ]);
        $this->assertJsonStringEqualsJsonString($expectedJson, (string) $statuses);
    }

    public function testJsonSerialize() : void
    {
        $data = 'ANP';
        $statuses = new AirRaidAlertOblastStatuses($data, false);

        $expectedJson = json_encode([
            ['oblast' => 'Автономна Республіка Крим', 'status' => 'active'],
            ['oblast' => 'Волинська область', 'status' => 'no_alert'],
            ['oblast' => 'Вінницька область', 'status' => 'partly'],
        ]);

        $this->assertJsonStringEqualsJsonString($expectedJson, json_encode($statuses));
    }

    public function testGetIterator()
    {
        $data = 'ANP';
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $this->assertInstanceOf(\ArrayIterator::class, $statuses->getIterator());
        $this->assertCount(3, iterator_to_array($statuses));
    }

    public function testGetNoAlertOblasts()
    {
        $data = 'ANNNANNNNNNNANNNNNNNNPNNNNA'; // 27 characters
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $noAlerts = array_values($statuses->getNoAlertOblasts());
        $this->assertCount(22, $noAlerts); // 22 'N's in the data
        $this->assertEquals('Волинська область', $noAlerts[0]->getOblast());
        $this->assertEquals('no_alert', $noAlerts[0]->getStatus());
    }

    public function testGetPartlyActiveAlertOblasts()
    {
        $data = 'ANNNANNNNNNNANNNNNNNNPNNNNA'; // 27 characters
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $partlyAlerts = array_values($statuses->getPartlyActiveAlertOblasts());
        $this->assertCount(1, $partlyAlerts); // 1 'P' in the data
        $this->assertEquals('Харківська область', $partlyAlerts[0]->getOblast());
        $this->assertEquals('partly', $partlyAlerts[0]->getStatus());
    }

    public function testToStringReturnsEmptyStringOnJsonEncodeFailure()
    {
        // Create a real AirRaidAlertOblastStatus object
        $realStatus = new AirRaidAlertOblastStatus('Test Oblast', 'A');

        // Create AirRaidAlertOblastStatuses with the manipulated object
        $statuses = new AirRaidAlertOblastStatuses('A', false); // Pass some dummy data
        $statusesReflection = new \ReflectionClass($statuses);
        $statusesProperty = $statusesReflection->getProperty('statuses');
        $statusesProperty->setAccessible(true);
        $statusesProperty->setValue($statuses, [$realStatus, fopen('php://memory', 'r')]); // Add a resource to the array

        // Assert that __toString() returns an empty string
        $this->assertEquals('', (string) $statuses);
    }
}
