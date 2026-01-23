<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\AirRaidAlertStatus;
use PHPUnit\Framework\TestCase;

class AirRaidAlertStatusTest extends TestCase
{
    public function testAirRaidAlertStatusGetters() : void
    {
        $locationTitle = 'Test Location';
        $status = 'active';
        $uid = 123;

        $airRaidAlertStatus = new AirRaidAlertStatus($locationTitle, $status, $uid);

        $this->assertEquals($locationTitle, $airRaidAlertStatus->getLocationTitle());
        $this->assertEquals($status, $airRaidAlertStatus->getStatus());
        $this->assertEquals($uid, $airRaidAlertStatus->getUid());
    }

    public function testAirRaidAlertStatusGettersWithNullUid() : void
    {
        $locationTitle = 'Another Location';
        $status = 'no_alert';

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
}
