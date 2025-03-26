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

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->location_title = $data['location_title'];
        $this->location_type = $data['location_type'];
        $this->started_at = UaDateParser::parseDate($data['started_at']);
        $this->finished_at = UaDateParser::parseDate($data['finished_at']);
        $this->updated_at = UaDateParser::parseDate($data['updated_at']);
        $this->alert_type = $data['alert_type'];
        $this->location_uid = $data['location_uid'];
        $this->location_oblast = $data['location_oblast'];
        $this->location_oblast_uid = $data['location_oblast_uid'];
        $this->location_raion = $data['location_raion'];
        $this->notes = $data['notes'];
        $this->calculated = $data['calculated'];
    }

    public function isFinished()
    {
        return null !== $this->finished_at;
    }
}
