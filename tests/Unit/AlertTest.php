<?php

namespace Tests\Unit;

use DateTime;
use Fyennyi\AlertsInUa\Model\Alert;
use PHPUnit\Framework\TestCase;

class AlertTest extends TestCase
{
    public function testAlertConstruction()
    {
        $data = [
            'id' => 123,
            'location_title' => 'Київ',
            'location_type' => 'city',
            'started_at' => '2023-01-02T10:15:30.000Z',
            'finished_at' => '2023-01-02T11:30:00.000Z',
            'updated_at' => '2023-01-02T11:30:00.000Z',
            'alert_type' => 'air_raid',
            'location_uid' => 31,
            'location_oblast' => 'м. Київ',
            'location_oblast_uid' => 31,
            'location_raion' => null,
            'notes' => 'Test alert',
            'calculated' => false
        ];

        $alert = new Alert($data);

        $this->assertEquals(123, $alert->getId());
        $this->assertEquals('Київ', $alert->getLocationTitle());
        $this->assertEquals('city', $alert->getLocationType());
        $this->assertEquals('air_raid', $alert->getAlertType());
        $this->assertEquals(31, $alert->getLocationUid());
        $this->assertEquals('м. Київ', $alert->getLocationOblast());
        $this->assertEquals(31, $alert->getLocationOblastUid());
        $this->assertEquals('Test alert', $alert->getNotes());
        $this->assertFalse($alert->isCalculated());
        $this->assertInstanceOf(DateTime::class, $alert->getStartedAt());
        $this->assertInstanceOf(DateTime::class, $alert->getFinishedAt());
        $this->assertInstanceOf(DateTime::class, $alert->getUpdatedAt());
        $this->assertTrue($alert->isFinished());
    }

    public function testAlertWithNulls()
    {
        $data = [
            'id' => 123,
            'location_title' => 'Київ',
            'finished_at' => null
        ];

        $alert = new Alert($data);

        $this->assertEquals(123, $alert->getId());
        $this->assertEquals('Київ', $alert->getLocationTitle());
        $this->assertNull($alert->getFinishedAt());
        $this->assertFalse($alert->isFinished());
    }
}
