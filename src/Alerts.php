<?php

namespace AlertsUA;

class Alerts
{
    private $alerts;

    private $last_updated_at;

    private $disclaimer;

    public function __construct($data)
    {
        $this->alerts = array_map(fn ($alert) => new Alert($alert), $data['alerts'] ?? []);
        $meta = $data['meta'] ?? [];
        $last_updated_at = $meta['last_updated_at'] ?? null;
        $this->last_updated_at = UaDateParser::parseDate($last_updated_at);
        $this->disclaimer = $data['disclaimer'] ?? '';
    }

    public function filter(...$args)
    {
        $filtered_alerts = $this->alerts;
        for ($i = 0; $i < count($args); $i += 2) {
            $filtered_alerts = array_filter($filtered_alerts, fn ($alert) => $alert->{$args[$i]} == $args[$i + 1]);
        }

        return $filtered_alerts;
    }

    public function getOblastAlerts()
    {
        return $this->getAlertsByAlertType('oblast');
    }

    public function getRaionAlerts()
    {
        return $this->getAlertsByAlertType('raion');
    }

    public function getHromadaAlerts()
    {
        return $this->getAlertsByAlertType('hromada');
    }

    public function getCityAlerts()
    {
        return $this->getAlertsByAlertType('city');
    }

    public function getAlertsByAlertType($alert_type)
    {
        return $this->filter('alert_type', $alert_type);
    }

    public function getAlertsByLocationTitle($location_title)
    {
        return $this->filter('location_title', $location_title);
    }

    public function getAlertsByLocationType($location_type)
    {
        return $this->filter('location_type', $location_type);
    }

    public function getAlertsByOblast($oblast_title)
    {
        return $this->filter('location_oblast', $oblast_title);
    }

    public function getAlertsByOblastUid($oblast_uid)
    {
        return $this->filter('location_oblast_uid', $oblast_uid);
    }

    public function getAlertsByLocationUid($location_uid)
    {
        return $this->filter('location_uid', $location_uid);
    }

    public function getAirRaidAlerts()
    {
        return $this->getAlertsByAlertType('air_raid');
    }

    public function getArtilleryShellingAlerts()
    {
        return $this->getAlertsByAlertType('artillery_shelling');
    }

    public function getUrbanFightsAlerts()
    {
        return $this->getAlertsByAlertType('urban_fights');
    }

    public function getNuclearAlerts()
    {
        return $this->getAlertsByAlertType('nuclear');
    }

    public function getChemicalAlerts()
    {
        return $this->getAlertsByAlertType('chemical');
    }

    public function getAllAlerts()
    {
        return $this->alerts;
    }

    public function getLastUpdatedAt()
    {
        return $this->last_updated_at;
    }

    public function getDisclaimer()
    {
        return $this->disclaimer;
    }

    public function __iter()
    {
        return new \ArrayIterator($this->alerts);
    }

    public function __repr()
    {
        return json_encode($this->alerts);
    }

    public function __len()
    {
        return count($this->alerts);
    }
}
