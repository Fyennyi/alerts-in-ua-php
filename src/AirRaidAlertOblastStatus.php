<?php

namespace AlertsUA;

class AirRaidAlertOblastStatus
{
    private $oblast;

    private $status;

    public function __construct($oblast, $status)
    {
        $this->oblast = $oblast;
        $this->status = $status;
    }

    public function getOblast()
    {
        return $this->oblast;
    }

    public function getStatus()
    {
        return $this->status;
    }
}
