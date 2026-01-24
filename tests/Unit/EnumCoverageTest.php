<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
use Fyennyi\AlertsInUa\Model\Enum\AlertType;
use Fyennyi\AlertsInUa\Model\Enum\LocationType;
use PHPUnit\Framework\TestCase;

class EnumCoverageTest extends TestCase
{
    public function testAlertStatusNullHandling()
    {
        $this->assertSame(AlertStatus::NO_ALERT, AlertStatus::fromString(null));
    }

    public function testAlertStatusJsonSerialize()
    {
        $status = AlertStatus::ACTIVE;
        $this->assertSame('active', $status->jsonSerialize());
        $this->assertSame('"active"', json_encode($status));
    }

    public function testAlertTypeJsonSerialize()
    {
        $type = AlertType::AIR_RAID;
        $this->assertSame('air_raid', $type->jsonSerialize());
        $this->assertSame('"air_raid"', json_encode($type));
    }

    public function testLocationTypeJsonSerialize()
    {
        $type = LocationType::CITY;
        $this->assertSame('city', $type->jsonSerialize());
        $this->assertSame('"city"', json_encode($type));
    }
}
