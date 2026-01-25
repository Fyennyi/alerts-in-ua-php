<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\AirRaidAlertStatus;
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
use PHPUnit\Framework\TestCase;

class AirRaidAlertStatusTest extends TestCase
{
    public function testAirRaidAlertStatusGetters() : void
    {
        $locationTitle = 'Test Location';
        $status = AlertStatus::ACTIVE;
        $uid = 123;

        $airRaidAlertStatus = new AirRaidAlertStatus($locationTitle, $status, $uid);

        $this->assertEquals($locationTitle, $airRaidAlertStatus->getLocationTitle());
        $this->assertEquals($status, $airRaidAlertStatus->getStatus());
        $this->assertEquals($uid, $airRaidAlertStatus->getUid());
        $this->assertTrue($airRaidAlertStatus->isActive());
        $this->assertFalse($airRaidAlertStatus->isPartlyActive());
        $this->assertFalse($airRaidAlertStatus->isNoAlert());
    }

    public function testAirRaidAlertStatusHelpers() : void
    {
        $statusActive = new AirRaidAlertStatus('Title', AlertStatus::ACTIVE);
        $this->assertTrue($statusActive->isActive());
        $this->assertFalse($statusActive->isPartlyActive());
        $this->assertFalse($statusActive->isNoAlert());

        $statusPartly = new AirRaidAlertStatus('Title', AlertStatus::PARTLY);
        $this->assertFalse($statusPartly->isActive());
        $this->assertTrue($statusPartly->isPartlyActive());
        $this->assertFalse($statusPartly->isNoAlert());

        $statusNoAlert = new AirRaidAlertStatus('Title', AlertStatus::NO_ALERT);
        $this->assertFalse($statusNoAlert->isActive());
        $this->assertFalse($statusNoAlert->isPartlyActive());
        $this->assertTrue($statusNoAlert->isNoAlert());
    }

    public function testAirRaidAlertStatusGettersWithNullUid() : void
    {
        $locationTitle = 'Another Location';
        $status = AlertStatus::NO_ALERT;

        $airRaidAlertStatus = new AirRaidAlertStatus($locationTitle, $status);

        $this->assertEquals($locationTitle, $airRaidAlertStatus->getLocationTitle());
        $this->assertEquals($status, $airRaidAlertStatus->getStatus());
        $this->assertNull($airRaidAlertStatus->getUid());
    }

    public function testJsonSerialize() : void
    {
        $status = new AirRaidAlertStatus('Test Location', 'active', 123);
        $expected = [
            'location_title' => 'Test Location',
            'status' => 'active',
            'uid' => 123,
        ];

        $this->assertEquals($expected, $status->jsonSerialize());
        $this->assertJsonStringEqualsJsonString(json_encode($expected), json_encode($status));
    }

    public function testToStringReturnsEmptyStringOnJsonEncodeFailure()
    {
        $status = new AirRaidAlertStatus('Test', AlertStatus::ACTIVE, 1);

        $reflection = new \ReflectionClass($status);
        $property = $reflection->getProperty('location_title');
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
