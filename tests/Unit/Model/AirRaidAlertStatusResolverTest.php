<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\AirRaidAlertStatusResolver;
use PHPUnit\Framework\TestCase;

class AirRaidAlertStatusResolverTest extends TestCase
{
    public function testResolveStatusChar()
    {
        $this->assertEquals('active', AirRaidAlertStatusResolver::resolveStatusChar('A'));
        $this->assertEquals('no_alert', AirRaidAlertStatusResolver::resolveStatusChar('N'));
        $this->assertEquals('partly', AirRaidAlertStatusResolver::resolveStatusChar('P'));
        $this->assertEquals('undefined', AirRaidAlertStatusResolver::resolveStatusChar(' '));
        $this->assertEquals('no_alert', AirRaidAlertStatusResolver::resolveStatusChar('X')); // Unknown char
    }

    public function testResolveStatusString()
    {
        $statusString = 'ANP';
        $mapping = [
            0 => 'Oblast 0',
            1 => 'Oblast 1',
            2 => 'Oblast 2'
        ];

        $expected = [
            0 => ['uid' => 0, 'location_title' => 'Oblast 0', 'status' => 'active'],
            1 => ['uid' => 1, 'location_title' => 'Oblast 1', 'status' => 'no_alert'],
            2 => ['uid' => 2, 'location_title' => 'Oblast 2', 'status' => 'partly'],
        ];

        $result = AirRaidAlertStatusResolver::resolveStatusString($statusString, $mapping);
        $this->assertEquals($expected, $result);
    }

    public function testResolveStatusStringWithUndefined()
    {
        $statusString = 'A '; // Active, Undefined (space)
        $mapping = [0 => 'Loc 0', 1 => 'Loc 1'];

        $result = AirRaidAlertStatusResolver::resolveStatusString($statusString, $mapping);

        $this->assertCount(1, $result);
        $this->assertEquals('active', $result[0]['status']);
        $this->assertArrayNotHasKey(1, $result);
    }

    public function testResolveStatusStringWithMissingMapping()
    {
        $statusString = 'A';
        $result = AirRaidAlertStatusResolver::resolveStatusString($statusString, []);

        $this->assertEquals('Локація #0', $result[0]['location_title']);
    }
}
