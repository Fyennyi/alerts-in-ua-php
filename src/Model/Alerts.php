<?php

namespace Fyennyi\AlertsInUa\Model;

use DateTime;
use Fyennyi\AlertsInUa\Util\UaDateParser;

class Alerts
{
    /** @var array<Alert> */
    private array $alerts;

    private ?DateTime $last_updated_at;

    private ?string $disclaimer;

    /**
     * Constructor for Alerts collection
     *
     * @param  array  $data  Raw alerts data from API
     */
    public function __construct(array $data)
    {
        $this->alerts = array_map(fn ($alert) => new Alert($alert), $data['alerts'] ?? []);
        $meta = $data['meta'] ?? [];
        $last_updated_at = $meta['last_updated_at'] ?? null;
        $this->last_updated_at = UaDateParser::parseDate($last_updated_at);
        $this->disclaimer = $data['disclaimer'] ?? '';
    }

    /**
     * Filter alerts by specified criteria
     *
     * @param  mixed  ...$args  Alternating field names and values to filter by
     * @return array Filtered alerts array
     */
    public function filter(mixed ...$args) : array
    {
        $filtered_alerts = $this->alerts;
        for ($i = 0; $i < count($args); $i += 2) {
            $filtered_alerts = array_filter($filtered_alerts, fn ($alert) => $alert->{$args[$i]} == $args[$i + 1]);
        }

        return $filtered_alerts;
    }

    /**
     * Get alerts for oblast level
     *
     * @return array Alerts for oblast level
     */
    public function getOblastAlerts() : array
    {
        return $this->getAlertsByLocationType('oblast');
    }

    /**
     * Get alerts for raion level
     *
     * @return array Alerts for raion level
     */
    public function getRaionAlerts() : array
    {
        return $this->getAlertsByLocationType('raion');
    }

    /**
     * Get alerts for hromada level
     *
     * @return array Alerts for hromada level
     */
    public function getHromadaAlerts() : array
    {
        return $this->getAlertsByLocationType('hromada');
    }

    /**
     * Get alerts for city level
     *
     * @return array Alerts for city level
     */
    public function getCityAlerts() : array
    {
        return $this->getAlertsByLocationType('city');
    }

    /**
     * Get alerts by alert type
     *
     * @param  string  $alert_type  Type of alert to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByAlertType(string $alert_type) : array
    {
        return $this->filter('alert_type', $alert_type);
    }

    /**
     * Get alerts by location title
     *
     * @param  string  $location_title  Location title to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByLocationTitle(string $location_title) : array
    {
        return $this->filter('location_title', $location_title);
    }

    /**
     * Get alerts by location type
     *
     * @param  string  $location_type  Location type to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByLocationType(string $location_type) : array
    {
        return $this->filter('location_type', $location_type);
    }

    /**
     * Get alerts by oblast
     *
     * @param  string  $oblast_title  Oblast title to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByOblast(string $oblast_title) : array
    {
        return $this->filter('location_oblast', $oblast_title);
    }

    /**
     * Get alerts by oblast UID
     *
     * @param  string  $oblast_uid  Oblast UID to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByOblastUid(string $oblast_uid) : array
    {
        return $this->filter('location_oblast_uid', $oblast_uid);
    }

    /**
     * Get alerts by location UID
     *
     * @param  string  $location_uid  Location UID to filter by
     * @return array Filtered alerts
     */
    public function getAlertsByLocationUid(string $location_uid) : array
    {
        return $this->filter('location_uid', $location_uid);
    }

    /**
     * Get air raid alerts
     *
     * @return array Air raid alerts
     */
    public function getAirRaidAlerts() : array
    {
        return $this->getAlertsByAlertType('air_raid');
    }

    /**
     * Get artillery shelling alerts
     *
     * @return array Artillery shelling alerts
     */
    public function getArtilleryShellingAlerts() : array
    {
        return $this->getAlertsByAlertType('artillery_shelling');
    }

    /**
     * Get urban fights alerts
     *
     * @return array Urban fights alerts
     */
    public function getUrbanFightsAlerts() : array
    {
        return $this->getAlertsByAlertType('urban_fights');
    }

    /**
     * Get nuclear alerts
     *
     * @return array Nuclear alerts
     */
    public function getNuclearAlerts() : array
    {
        return $this->getAlertsByAlertType('nuclear');
    }

    /**
     * Get chemical alerts
     *
     * @return array Chemical alerts
     */
    public function getChemicalAlerts() : array
    {
        return $this->getAlertsByAlertType('chemical');
    }

    /**
     * Get all alerts in collection
     *
     * @return array All alerts
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
    public function getLastUpdatedAt() : DateTime|null
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
     * @return \ArrayIterator Iterator for alerts
     */
    public function __iter() : \ArrayIterator
    {
        return new \ArrayIterator($this->alerts);
    }

    /**
     * Get string representation of alerts collection
     *
     * @return string JSON encoded alerts array
     */
    public function __repr() : string
    {
        return json_encode($this->alerts);
    }

    /**
     * Get count of alerts in collection
     *
     * @return int Number of alerts
     */
    public function __len() : int
    {
        return count($this->alerts);
    }
}
