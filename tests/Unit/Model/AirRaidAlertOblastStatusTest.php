<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use PHPUnit\Framework\TestCase;

class AirRaidAlertOblastStatusTest extends TestCase
{
    public function testIsActive()
    {
        $status = new AirRaidAlertOblastStatus('Тестова область', 'A');
        $this->assertTrue($status->isActive());
        $this->assertFalse($status->isPartlyActive());
        $this->assertFalse($status->isNoAlert());
    }

    public function testIsPartlyActive()
    {
        $status = new AirRaidAlertOblastStatus('Тестова область', 'P');
        $this->assertFalse($status->isActive());
        $this->assertTrue($status->isPartlyActive());
        $this->assertFalse($status->isNoAlert());
    }

    public function testIsNoAlert()
    {
        $status = new AirRaidAlertOblastStatus('Тестова область', 'N');
        $this->assertFalse($status->isActive());
        $this->assertFalse($status->isPartlyActive());
        $this->assertTrue($status->isNoAlert());
    }

    public function testToString()
    {
        $status = new AirRaidAlertOblastStatus('Тестова область', 'A');
        $this->assertJsonStringEqualsJsonString('{"oblast":"Тестова область","status":"active"}', (string) $status);

        $status = new AirRaidAlertOblastStatus('Інша область', 'P');
        $this->assertJsonStringEqualsJsonString('{"oblast":"Інша область","status":"partly"}', (string) $status);

        $status = new AirRaidAlertOblastStatus('Третя область', 'N');
        $this->assertJsonStringEqualsJsonString('{"oblast":"Третя область","status":"no_alert"}', (string) $status);
    }

    public function testOblastLevelOnlyImpact()
    {
        // 'P' becomes 'no_alert' when oblast_level_only is true
        $status = new AirRaidAlertOblastStatus('Тестова область', 'P', true);
        $this->assertFalse($status->isPartlyActive());
        $this->assertTrue($status->isNoAlert());

        // 'A' remains 'active'
        $status = new AirRaidAlertOblastStatus('Тестова область', 'A', true);
        $this->assertTrue($status->isActive());
    }

    public function testJsonSerialize()
    {
        $status = new AirRaidAlertOblastStatus('Тестова область', 'A');
        $expected = [
            'oblast' => 'Тестова область',
            'status' => 'active',
        ];
        $this->assertEquals($expected, $status->jsonSerialize());

        $status = new AirRaidAlertOblastStatus('Інша область', 'P', true);
        $expected = [
            'oblast' => 'Інша область',
            'status' => 'no_alert', // 'P' becomes 'no_alert' due to oblast_level_only
        ];
        $this->assertEquals($expected, $status->jsonSerialize());
    }

    public function testToStringReturnsEmptyStringOnJsonEncodeFailure()
    {
        $status = new AirRaidAlertOblastStatus('Test', 'A');

        $reflection = new \ReflectionClass($status);
        $property = $reflection->getProperty('oblast');
        $property->setAccessible(true);
        // Insert invalid UTF-8
        $property->setValue($status, "\xB1\x31");

        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            $this->assertEquals('', (string)$status);
        } finally {
            ini_set('error_log', $originalErrorLog);
        }
    }
}
