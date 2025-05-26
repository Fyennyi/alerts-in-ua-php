<?php

namespace AlertsUA;

class AirRaidAlertOblastStatus
{
    private $oblast;

    private $status;

    /**
     * Constructor for AirRaidAlertOblastStatus
     *
     * @param  string  $oblast  Oblast name
     * @param  string  $status  Alert status code
     */
    public function __construct($oblast, $status)
    {
        $this->oblast = $oblast;
        $this->status = $status;
    }

    /**
     * Get oblast name
     *
     * @return string Oblast name
     */
    public function getOblast()
    {
        return $this->oblast;
    }

    /**
     * Get alert status
     *
     * @return string Alert status code
     */
    public function getStatus()
    {
        return $this->status;
    }
}
