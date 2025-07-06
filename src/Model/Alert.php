<?php

namespace Fyennyi\AlertsInUa\Model;

use Fyennyi\AlertsInUa\Util\UaDateParser;

class Alert
{
    public int $id;

    public string $location_title;

    public ?string $location_type;

    public ?\DateTimeInterface $started_at;

    public ?\DateTimeInterface $finished_at;

    public ?\DateTimeInterface $updated_at;

    public ?string $alert_type;

    public ?int $location_uid;

    public ?string $location_oblast;

    public ?int $location_oblast_uid;

    public ?string $location_raion;

    public ?string $notes;

    public bool $calculated;

    /**
     * Constructor for Alert
     *
     * @param  array<string, mixed>  $data  Raw alert data from API
     */
    public function __construct(array $data)
    {
        $this->id = isset($data['id']) && is_int($data['id']) ? $data['id'] : 0;
        $this->location_title = isset($data['location_title']) && is_string($data['location_title']) ? $data['location_title'] : '';
        $this->location_type = isset($data['location_type']) && is_string($data['location_type']) ? $data['location_type'] : null;
        $this->started_at = isset($data['started_at']) && is_string($data['started_at']) ? UaDateParser::parseDate($data['started_at']) : null;
        $this->finished_at = isset($data['finished_at']) && is_string($data['finished_at']) ? UaDateParser::parseDate($data['finished_at']) : null;
        $this->updated_at = isset($data['updated_at']) && is_string($data['updated_at']) ? UaDateParser::parseDate($data['updated_at']) : null;
        $this->alert_type = isset($data['alert_type']) && is_string($data['alert_type']) ? $data['alert_type'] : null;
        $this->location_uid = isset($data['location_uid']) && (is_int($data['location_uid']) || is_string($data['location_uid'])) ? (int) $data['location_uid'] : null;
        $this->location_oblast = isset($data['location_oblast']) && is_string($data['location_oblast']) ? $data['location_oblast'] : null;
        $this->location_oblast_uid = isset($data['location_oblast_uid']) && (is_int($data['location_oblast_uid']) || is_string($data['location_oblast_uid'])) ? (int) $data['location_oblast_uid'] : null;
        $this->location_raion = isset($data['location_raion']) && is_string($data['location_raion']) ? $data['location_raion'] : null;
        $this->notes = isset($data['notes']) && is_string($data['notes']) ? $data['notes'] : null;
        $this->calculated = isset($data['calculated']) ? (bool) $data['calculated'] : false;
    }

    /**
     * Check if alert is finished
     *
     * @return bool True if alert has finished_at date set
     */
    public function isFinished() : bool
    {
        return null !== $this->finished_at;
    }
}
