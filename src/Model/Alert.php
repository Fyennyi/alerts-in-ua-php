<?php

/*
 *
 *     _    _           _       ___       _   _
 *    / \  | | ___ _ __| |_ ___|_ _|_ __ | | | | __ _
 *   / _ \ | |/ _ \ '__| __/ __|| || '_ \| | | |/ _` |
 *  / ___ \| |  __/ |  | |_\__ \| || | | | |_| | (_| |
 * /_/   \_\_|\___|_|   \__|___/___|_| |_|\___/ \__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author Serhii Cherneha
 * @link https://chernega.eu.org/
 *
 *
 */

namespace Fyennyi\AlertsInUa\Model;

use DateInterval;
use DateTimeImmutable;
use Fyennyi\AlertsInUa\Util\UaDateParser;
use JsonSerializable;

class Alert implements JsonSerializable
{
    private int $id;

    private string $location_title;

    private ?string $location_type;

    private ?\DateTimeInterface $started_at;

    private ?\DateTimeInterface $finished_at;

    private ?\DateTimeInterface $updated_at;

    private ?string $alert_type;

    private ?int $location_uid;

    private ?string $location_oblast;

    private ?int $location_oblast_uid;

    private ?string $location_raion;

    private ?string $notes;

    private bool $calculated;

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
     * Get alert unique identifier
     *
     * @return int Alert ID
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * Get location title (name)
     *
     * @return string Location title
     */
    public function getLocationTitle() : string
    {
        return $this->location_title;
    }

    /**
     * Get location type (oblast, raion, hromada, city, etc.)
     *
     * @return string|null Location type or null if not specified
     */
    public function getLocationType() : ?string
    {
        return $this->location_type;
    }

    /**
     * Get alert start timestamp
     *
     * @return \DateTimeInterface|null Alert start date and time in Kyiv timezone or null if not set
     */
    public function getStartedAt() : ?\DateTimeInterface
    {
        return $this->started_at;
    }

    /**
     * Get alert finish timestamp
     *
     * @return \DateTimeInterface|null Alert finish date and time in Kyiv timezone or null if still active
     */
    public function getFinishedAt() : ?\DateTimeInterface
    {
        return $this->finished_at;
    }

    /**
     * Get alert last update timestamp
     *
     * @return \DateTimeInterface|null Alert last update date and time in Kyiv timezone or null if not set
     */
    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    /**
     * Get alert type (air_raid, artillery_shelling, urban_fights, nuclear, chemical, etc.)
     *
     * @return string|null Alert type or null if not specified
     */
    public function getAlertType() : ?string
    {
        return $this->alert_type;
    }

    /**
     * Get location unique identifier
     *
     * @return int|null Location UID or null if not specified
     */
    public function getLocationUid() : ?int
    {
        return $this->location_uid;
    }

    /**
     * Get oblast (region) name where the location is situated
     *
     * @return string|null Oblast name or null if not specified
     */
    public function getLocationOblast() : ?string
    {
        return $this->location_oblast;
    }

    /**
     * Get oblast (region) unique identifier
     *
     * @return int|null Oblast UID or null if not specified
     */
    public function getLocationOblastUid() : ?int
    {
        return $this->location_oblast_uid;
    }

    /**
     * Get raion (district) name where the location is situated
     *
     * @return string|null Raion name or null if not specified
     */
    public function getLocationRaion() : ?string
    {
        return $this->location_raion;
    }

    /**
     * Get additional notes or comments about the alert
     *
     * @return string|null Alert notes or null if not specified
     */
    public function getNotes() : ?string
    {
        return $this->notes;
    }

    /**
     * Check if the alert end time is estimated
     *
     * @return bool True if the end time is estimated, false if it is the actual end time
     */
    public function isCalculated() : bool
    {
        return $this->calculated;
    }

    /**
     * Get property value by name (for backward compatibility)
     *
     * @param  string  $property  Property name
     * @return mixed Property value
     */
    public function getProperty(string $property) : mixed
    {
        return match ($property) {
            'id' => $this->id,
            'location_title' => $this->location_title,
            'location_type' => $this->location_type,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'updated_at' => $this->updated_at,
            'alert_type' => $this->alert_type,
            'location_uid' => $this->location_uid,
            'location_oblast' => $this->location_oblast,
            'location_oblast_uid' => $this->location_oblast_uid,
            'location_raion' => $this->location_raion,
            'notes' => $this->notes,
            'calculated' => $this->calculated,
            default => null
        };
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

    /**
     * Check if alert is currently active
     *
     * @return bool True if alert doesn't have finished_at date set
     */
    public function isActive() : bool
    {
        return null === $this->finished_at;
    }

    /**
     * Get the duration of the alert
     *
     * @return DateInterval|null Duration as DateInterval or null if couldn't calculate
     */
    public function getDuration() : ?DateInterval
    {
        if (null === $this->started_at) {
            return null;
        }

        $end_time = $this->finished_at ?? new DateTimeImmutable();

        return $this->started_at->diff($end_time);
    }

    /**
     * Get the duration in seconds
     *
     * @return int|null Duration in seconds or null if couldn't calculate
     */
    public function getDurationInSeconds() : ?int
    {
        if (null === $this->started_at) {
            return null;
        }

        $end_time = $this->finished_at ?? new DateTimeImmutable();

        return $end_time->getTimestamp() - $this->started_at->getTimestamp();
    }

    /**
     * Check if alert is of specific type
     *
     * @param  string  $type  Alert type to check (air_raid, artillery_shelling, etc.)
     * @return bool True if alert matches the type
     */
    public function isType(string $type) : bool
    {
        return $type === $this->alert_type;
    }

    /**
     * Check if alert is in specific location
     *
     * @param  string  $location  Location name to check
     * @return bool True if alert is in the specified location
     */
    public function isInLocation(string $location) : bool
    {
        return false !== stripos($this->location_title, $location)
            || (null !== $this->location_oblast && false !== stripos($this->location_oblast, $location))
            || (null !== $this->location_raion && false !== stripos($this->location_raion, $location));
    }

    /**
     * Get alert as array representation
     *
     * @return array<string, mixed> Array representation of the alert
     */
    public function toArray() : array
    {
        return [
            'id' => $this->id,
            'location_title' => $this->location_title,
            'location_type' => $this->location_type,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'finished_at' => $this->finished_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'alert_type' => $this->alert_type,
            'location_uid' => $this->location_uid,
            'location_oblast' => $this->location_oblast,
            'location_oblast_uid' => $this->location_oblast_uid,
            'location_raion' => $this->location_raion,
            'notes' => $this->notes,
            'calculated' => $this->calculated,
            'is_active' => $this->isActive(),
            'duration' => $this->getDurationInSeconds(),
        ];
    }

    /**
     * Get alert as JSON representation
     *
     * @return string JSON representation of the alert
     *
     * @throws \RuntimeException If JSON encoding fails
     */
    public function toJson() : string
    {
        try {
            return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode alert to JSON: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }

    /**
     * Get string representation of the alert
     *
     * @return string JSON representation
     */
    public function __toString() : string
    {
        try {
            return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Failed to serialize Alert to string: ' . $e->getMessage());
            return '';
        }
    }
}
