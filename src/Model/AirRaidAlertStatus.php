<?php

namespace Fyennyi\AlertsInUa\Model;

class AirRaidAlertStatus
{
    private string $location_title;

    private string $status;

    private ?int $uid;

    /**
     * AirRaidAlertStatus constructor
     *
     * @param  string  $location_title  The title/name of the location
     * @param  string  $status  The alert status ('no_alert', 'active', 'partly')
     * @param  int|null  $uid  The UID of the location
     */
    public function __construct(string $location_title, string $status, ?int $uid = null)
    {
        $this->location_title = $location_title;
        $this->status = $status;
        $this->uid = $uid;
    }

    /**
     * Get the location title
     *
     * @return string The location title
     */
    public function getLocationTitle() : string
    {
        return $this->location_title;
    }

    /**
     * Get the alert status
     *
     * @return string The alert status
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Get the location UID
     *
     * @return int|null The location UID
     */
    public function getUid() : ?int
    {
        return $this->uid;
    }
}
