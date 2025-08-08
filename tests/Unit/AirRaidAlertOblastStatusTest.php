<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use PHPUnit\Framework\TestCase;

class AirRaidAlertOblastStatusTest extends TestCase
{
    public function testIsActive()
    {
        $status = new AirRaidAlertOblastStatus('������� �������', 'A');
        $this->assertTrue($status->isActive());
        $this->assertFalse($status->isPartlyActive());
        $this->assertFalse($status->isNoAlert());
    }

    public function testIsPartlyActive()
    {
        $status = new AirRaidAlertOblastStatus('������� �������', 'P');
        $this->assertFalse($status->isActive());
        $this->assertTrue($status->isPartlyActive());
        $this->assertFalse($status->isNoAlert());
    }

    public function testIsNoAlert()
    {
        $status = new AirRaidAlertOblastStatus('������� �������', 'N');
        $this->assertFalse($status->isActive());
        $this->assertFalse($status->isPartlyActive());
        $this->assertTrue($status->isNoAlert());
    }

    public function testToString()
    {
        $status = new AirRaidAlertOblastStatus('������� �������', 'A');
        $this->assertEquals('active:������� �������', (string) $status);

        $status = new AirRaidAlertOblastStatus('���� �������', 'P');
        $this->assertEquals('partly:���� �������', (string) $status);

        $status = new AirRaidAlertOblastStatus('����� �������', 'N');
        $this->assertEquals('no_alert:����� �������', (string) $status);
    }

    public function testOblastLevelOnlyImpact()
    {
        // 'P' becomes 'no_alert' when oblast_level_only is true
        $status = new AirRaidAlertOblastStatus('������� �������', 'P', true);
        $this->assertFalse($status->isPartlyActive());
        $this->assertTrue($status->isNoAlert());

        // 'A' remains 'active'
        $status = new AirRaidAlertOblastStatus('������� �������', 'A', true);
        $this->assertTrue($status->isActive());
    }

    public function testJsonSerialize()
    {
        $status = new AirRaidAlertOblastStatus('������� �������', 'A');
        $expected = [
            'oblast' => '������� �������',
            'status' => 'active',
        ];
        $this->assertEquals($expected, $status->jsonSerialize());

        $status = new AirRaidAlertOblastStatus('���� �������', 'P', true);
        $expected = [
            'oblast' => '���� �������',
            'status' => 'no_alert', // 'P' becomes 'no_alert' due to oblast_level_only
        ];
        $this->assertEquals($expected, $status->jsonSerialize());
    }
}
