<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\Enum\AlertLevel;
use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
use Fyennyi\AlertsInUa\Model\Enum\AlertType;
use Fyennyi\AlertsInUa\Model\Enum\LocationType;
use Fyennyi\AlertsInUa\Model\Enum\ThreatType;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
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

    public function testAlertLevelJsonSerialize()
    {
        $level = AlertLevel::RED;
        $this->assertSame('red', $level->jsonSerialize());
        $this->assertSame('"red"', json_encode($level));
    }

    public function testThreatTypeJsonSerialize()
    {
        $type = ThreatType::DRONES;
        $this->assertSame('drones', $type->jsonSerialize());
        $this->assertSame('"drones"', json_encode($type));
    }
}
