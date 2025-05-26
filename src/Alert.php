<?php

namespace AlertsUA;

class Alert
{
    public $id;

    public $location_title;

    public $location_type;

    public $started_at;

    public $finished_at;

    public $updated_at;

    public $alert_type;

    public $location_uid;

    public $location_oblast;

    public $location_oblast_uid;

    public $location_raion;

    public $notes;

    public $calculated;

    /**
     * Constructor for Alert
     *
     * @param  array  $data  Raw alert data from API
     */
    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->location_title = $data['location_title'] ?? null;
        $this->location_type = $data['location_type'] ?? null;
        $this->started_at = UaDateParser::parseDate($data['started_at'] ?? null);
        $this->finished_at = UaDateParser::parseDate($data['finished_at'] ?? null);
        $this->updated_at = UaDateParser::parseDate($data['updated_at'] ?? null);
        $this->alert_type = $data['alert_type'] ?? null;
        $this->location_uid = $data['location_uid'] ?? null;
        $this->location_oblast = $data['location_oblast'] ?? null;
        $this->location_oblast_uid = $data['location_oblast_uid'] ?? null;
        $this->location_raion = $data['location_raion'] ?? null;
        $this->notes = $data['notes'] ?? null;
        $this->calculated = $data['calculated'] ?? false;
    }

    /**
     * Check if alert is finished
     *
     * @return bool True if alert has finished_at date set
     */
    public function isFinished()
    {
        return null !== $this->finished_at;
    }
}
