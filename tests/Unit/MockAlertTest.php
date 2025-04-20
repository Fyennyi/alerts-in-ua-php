<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use AlertsUA\AirRaidAlertOblastStatus;

class MockAlertTest extends TestCase
{
    public function testAirRaidAlertOblastStatus()
    {
        $oblast = 'м. Київ';
        $status = 'A';
        
        $alertStatus = new AirRaidAlertOblastStatus($oblast, $status);
        
        $this->assertEquals($oblast, $alertStatus->getOblast());
        $this->assertEquals($status, $alertStatus->getStatus());
    }
}
