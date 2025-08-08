<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatus;
use Fyennyi\AlertsInUa\Model\AirRaidAlertOblastStatuses;
use PHPUnit\Framework\TestCase;

class AirRaidAlertOblastStatusesTest extends TestCase
{
    public function testStatusesWithOblastLevelOnly()
    {
        $data = 'ANBCAP'; // Simulated status data for 6 oblasts (A: active, N: no_alert, P: partly)
        $statuses = new AirRaidAlertOblastStatuses($data, true);

        // When oblast_level_only is true, 'P' (partly) becomes 'no_alert'.
        // All 6 statuses should be included, but their resolved status will be 'active' or 'no_alert'.
        $this->assertCount(6, $statuses->getStatuses());

        $expectedStatuses = ['active', 'no_alert', 'no_alert', 'no_alert', 'active', 'no_alert'];
        foreach ($statuses->getStatuses() as $index => $status) {
            $this->assertInstanceOf(AirRaidAlertOblastStatus::class, $status);
            $this->assertEquals($expectedStatuses[$index], $status->getStatus());
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
}
