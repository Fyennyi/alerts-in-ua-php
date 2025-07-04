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
}
