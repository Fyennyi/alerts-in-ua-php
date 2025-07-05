<?php

namespace Fyennyi\AlertsInUa\Model;

class AirRaidAlertOblastStatus
{
    private string $oblast;

    private string $status;

    /**
     * Constructor for AirRaidAlertOblastStatus
     *
     * @param  string  $oblast  Oblast name
     * @param  string  $status  Alert status code
     */
    public function __construct(string $oblast, string $status)
    {
        $this->oblast = $oblast;
        $this->status = $status;
    }

    /**
     * Get oblast name
     *
     * @return string Oblast name
     */
    public function getOblast() : string
    {
        return $this->oblast;
    }

    /**
     * Get alert status
     *
     * @return string Alert status code
     */
    public function getStatus() : string
    {
        return $this->status;
    }
}
