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

use ArrayIterator;
use Countable;
use DateTime;
use Fyennyi\AlertsInUa\Model\Enum\AlertType;
use Fyennyi\AlertsInUa\Model\Enum\LocationType;
use Fyennyi\AlertsInUa\Util\UaDateParser;
use IteratorAggregate;
use JsonSerializable;

/**
 * @implements IteratorAggregate<int, Alert>
 */
class Alerts implements Countable, IteratorAggregate, JsonSerializable
{
    use XmlSerializableTrait;

    /** @var list<Alert> */
    private array $alerts;

    private ?DateTime $last_updated_at;

    private string $disclaimer;

    /**
     * Constructor for Alerts collection
     *
     * @param  array<string|int, mixed>  $data  Raw alerts data from API
     */
    public function __construct(array $data)
    {
        $alerts_data = $data['alerts'] ?? [];
        if (! is_array($alerts_data)) {
            $alerts_data = [];
        }

        $this->alerts = [];
        foreach ($alerts_data as $alert) {
            if (! is_array($alert)) {
                continue;
            }

            /** @var array<string, mixed> $alert */
            $this->alerts[] = new Alert($alert);
        }

        $meta = $data['meta'] ?? [];
        if (! is_array($meta)) {
            $meta = [];
        }

        $last_updated_at = $meta['last_updated_at'] ?? null;
        $this->last_updated_at = is_string($last_updated_at) ? UaDateParser::parseDate($last_updated_at) : null;

        $disclaimer = $data['disclaimer'] ?? '';
        $this->disclaimer = is_string($disclaimer) ? $disclaimer : '';
    }

    /**
     * Filter alerts by specified criteria
     *
     * @param  mixed  ...$args  Alternating field names and values to filter by
     * @return list<Alert> Filtered alerts array
     */
    public function filter(mixed ...$args) : array
    {
        $filtered_alerts = $this->alerts;
        for ($i = 0; $i < count($args); $i += 2) {
            if (! isset($args[$i]) || ! isset($args[$i + 1])) {
                break;
            }
            $field = $args[$i];
            $value = $args[$i + 1];

            if (! is_string($field)) {
                continue;
            }

            $filtered_alerts = array_filter($filtered_alerts, function ($alert) use ($field, $value) {
                $property_value = $alert->getProperty($field);

                // Handle Enum comparison
                if ($property_value instanceof \BackedEnum) {
                    if ($value instanceof \BackedEnum) {
                        return $property_value === $value;
                    }
                    return $property_value->value === $value;
                }

                return $property_value == $value;
            });
        }

        return array_values($filtered_alerts);
    }

    /**
     * Get alerts for oblast level
     *
     * @return list<Alert> Alerts for oblast level
     */
    public function getOblastAlerts() : array
    {
        return $this->getAlertsByLocationType(LocationType::OBLAST);
    }

    /**
     * Get alerts for raion level
     *
     * @return list<Alert> Alerts for raion level
     */
    public function getRaionAlerts() : array
    {
        return $this->getAlertsByLocationType(LocationType::RAION);
    }

    /**
     * Get alerts for hromada level
     *
     * @return list<Alert> Alerts for hromada level
     */
    public function getHromadaAlerts() : array
    {
        return $this->getAlertsByLocationType(LocationType::HROMADA);
    }

    /**
     * Get alerts for city level
     *
     * @return list<Alert> Alerts for city level
     */
    public function getCityAlerts() : array
    {
        return $this->getAlertsByLocationType(LocationType::CITY);
    }

    /**
     * Get alerts by alert type
     *
     * @param  AlertType|string  $alert_type  Type of alert to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByAlertType(AlertType|string $alert_type) : array
    {
        return $this->filter('alert_type', $alert_type);
    }

    /**
     * Get alerts by location title
     *
     * @param  string  $location_title  Location title to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByLocationTitle(string $location_title) : array
    {
        return $this->filter('location_title', $location_title);
    }

    /**
     * Get alerts by location type
     *
     * @param  LocationType|string  $location_type  Location type to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByLocationType(LocationType|string $location_type) : array
    {
        return $this->filter('location_type', $location_type);
    }

    /**
     * Get alerts by oblast
     *
     * @param  string  $oblast_title  Oblast title to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByOblast(string $oblast_title) : array
    {
        return $this->filter('location_oblast', $oblast_title);
    }

    /**
     * Get alerts by oblast UID
     *
     * @param  string  $oblast_uid  Oblast UID to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByOblastUid(string $oblast_uid) : array
    {
        return $this->filter('location_oblast_uid', $oblast_uid);
    }

    /**
     * Get alerts by location UID
     *
     * @param  string  $location_uid  Location UID to filter by
     * @return list<Alert> Filtered alerts
     */
    public function getAlertsByLocationUid(string $location_uid) : array
    {
        return $this->filter('location_uid', $location_uid);
    }

    /**
     * Get air raid alerts
     *
     * @return list<Alert> Air raid alerts
     */
    public function getAirRaidAlerts() : array
    {
        return $this->getAlertsByAlertType(AlertType::AIR_RAID);
    }

    /**
     * Get artillery shelling alerts
     *
     * @return list<Alert> Artillery shelling alerts
     */
    public function getArtilleryShellingAlerts() : array
    {
        return $this->getAlertsByAlertType(AlertType::ARTILLERY_SHELLING);
    }

    /**
     * Get urban fights alerts
     *
     * @return list<Alert> Urban fights alerts
     */
    public function getUrbanFightsAlerts() : array
    {
        return $this->getAlertsByAlertType(AlertType::URBAN_FIGHTS);
    }

    /**
     * Get nuclear alerts
     *
     * @return list<Alert> Nuclear alerts
     */
    public function getNuclearAlerts() : array
    {
        return $this->getAlertsByAlertType(AlertType::NUCLEAR);
    }

    /**
     * Get chemical alerts
     *
     * @return list<Alert> Chemical alerts
     */
    public function getChemicalAlerts() : array
    {
        return $this->getAlertsByAlertType(AlertType::CHEMICAL);
    }

    /**
     * Get all alerts in collection
     *
     * @return list<Alert> All alerts
     */
    public function getAllAlerts() : array
    {
        return $this->alerts;
    }

    /**
     * Get last updated timestamp
     *
     * @return DateTime|null Last updated timestamp
     */
    public function getLastUpdatedAt() : ?DateTime
    {
        return $this->last_updated_at;
    }

    /**
     * Get disclaimer text
     *
     * @return string Disclaimer text
     */
    public function getDisclaimer() : string
    {
        return $this->disclaimer;
    }

    /**
     * Get iterator for alerts collection
     *
     * @return ArrayIterator<int, Alert> Iterator for alerts
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->alerts);
    }

    /**
     * Get count of alerts in collection
     *
     * @return int<0, max> Number of alerts
     */
    public function count() : int
    {
        return count($this->alerts);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array{alerts: list<Alert>, meta: array{last_updated_at: string|null, count: int}, disclaimer: string} Data to be serialized
     */
    public function jsonSerialize() : array
    {
        return [
            'alerts' => $this->alerts,
            'meta' => [
                'last_updated_at' => $this->last_updated_at?->format('c'),
                'count' => $this->count()
            ],
            'disclaimer' => $this->disclaimer
        ];
    }

    /**
     * Get string representation of alerts collection
     *
     * @return string JSON encoded alerts array
     */
    public function __toString() : string
    {
        try {
            return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Failed to serialize Alerts to string: ' . $e->getMessage());
            return '';
        }
    }
}