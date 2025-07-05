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

    public ?string $location_uid;

    public ?string $location_oblast;

    public ?string $location_oblast_uid;

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
    public function isFinished() : bool
    {
        return null !== $this->finished_at;
    }
}
