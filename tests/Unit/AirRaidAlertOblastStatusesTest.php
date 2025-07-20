<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use PHPUnit\Framework\TestCase;

class AirRaidAlertOblastStatusesTest extends TestCase
{
    public function testStatusesWithOblastLevelOnly()
    {
        $data = 'ANBCAD'; // Simulated status data for 6 oblasts
        $statuses = new AirRaidAlertOblastStatuses($data, true);

        // Since oblast_level_only is true, only 'A' statuses should be included
        $this->assertCount(2, $statuses->getStatuses());

        foreach ($statuses->getStatuses() as $status) {
            $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $status);
            $this->assertEquals('A', $status->getStatus());
        }
    }

    public function testStatusesWithAllLevels()
    {
        $data = 'ANBCAD'; // Simulated status data for 6 oblasts
        $statuses = new AirRaidAlertOblastStatuses($data, false);

        // All statuses should be included
        $this->assertCount(6, $statuses->getStatuses());
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

    public function testWithShorterStatusString()
    {
        $data = 'A';
        $statuses = new AirRaidAlertOblastStatuses($data, false);
        $this->assertCount(1, $statuses->getStatuses());
    }
}
