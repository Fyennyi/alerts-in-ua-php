<?php

namespace Fyennyi\AlertsInUa\Client;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\SimpleCache\CacheInterface;

trait GeoLocationTrait
{
    private \Fyennyi\AlertsInUa\Util\NominatimGeoResolver $geo_resolver;

    public function getAlertsByCoordinatesAsync(
        float $lat,
        float $lon,
        string $period = 'week_ago',
        bool $use_cache = false
    ): PromiseInterface {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new \Fyennyi\AlertsInUa\Util\NominatimGeoResolver(null, $this->cache ?? null);
        }

        /** @var array{uid: int, name: string, matched_by: string}|null $location */
        $location = $this->geo_resolver->findByCoordinates($lat, $lon);

        if ($location === null) {
            throw new InvalidParameterException(
                sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
            );
        }

        $uid = $location['uid'];
        return $this->getAlertsHistoryAsync($uid, $period, $use_cache);
    }

    public function getAirRaidAlertStatusByCoordinatesAsync(
        float $lat,
        float $lon,
        bool $oblast_level_only = false,
        bool $use_cache = false
    ): PromiseInterface {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new \Fyennyi\AlertsInUa\Util\NominatimGeoResolver(null, $this->cache ?? null);
        }

        /** @var array{uid: int, name: string, matched_by: string}|null $location */
        $location = $this->geo_resolver->findByCoordinates($lat, $lon);

        if ($location === null) {
            throw new InvalidParameterException(
                sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
            );
        }

        $uid = $location['uid'];
        return $this->getAirRaidAlertStatusAsync(
            $uid,
            $oblast_level_only,
            $use_cache
        );
    }
}
