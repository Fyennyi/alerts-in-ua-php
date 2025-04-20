<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use AlertsUA\Alert;

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

        $this->assertEquals(123, $alert->id);
        $this->assertEquals('Київ', $alert->location_title);
        $this->assertEquals('city', $alert->location_type);
        $this->assertEquals('air_raid', $alert->alert_type);
        $this->assertEquals(31, $alert->location_uid);
        $this->assertEquals('м. Київ', $alert->location_oblast);
        $this->assertEquals(31, $alert->location_oblast_uid);
        $this->assertEquals('Test alert', $alert->notes);
        $this->assertFalse($alert->calculated);
        $this->assertInstanceOf(DateTime::class, $alert->started_at);
        $this->assertInstanceOf(DateTime::class, $alert->finished_at);
        $this->assertInstanceOf(DateTime::class, $alert->updated_at);
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

        $this->assertEquals(123, $alert->id);
        $this->assertEquals('Київ', $alert->location_title);
        $this->assertNull($alert->finished_at);
        $this->assertFalse($alert->isFinished());
    }
}
