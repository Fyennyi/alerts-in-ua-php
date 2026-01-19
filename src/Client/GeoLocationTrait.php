<?php

namespace Fyennyi\AlertsInUa\Client;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\Alerts;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

trait GeoLocationTrait
{
    private NominatimGeoResolver $geo_resolver;

    public function getAlertsByCoordinatesAsync(
        float $lat,
        float $lon,
        string $period = 'week_ago',
        bool $use_cache = false
    ): PromiseInterface {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new NominatimGeoResolver(null, $this->cache ?? null);
        }

        $location = $this->geo_resolver->findByCoordinates($lat, $lon);

        if ($location === null) {
            throw new InvalidParameterException(
                sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
            );
        }

        return $this->getAlertsHistoryAsync($location['uid'], $period, $use_cache);
    }

    public function getAirRaidAlertStatusByCoordinatesAsync(
        float $lat,
        float $lon,
        bool $oblast_level_only = false,
        bool $use_cache = false
    ): PromiseInterface {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new NominatimGeoResolver(null, $this->cache ?? null);
        }

        $location = $this->geo_resolver->findByCoordinates($lat, $lon);

        if ($location === null) {
            throw new InvalidParameterException(
                sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
            );
        }

        return $this->getAirRaidAlertStatusAsync(
            $location['uid'],
            $oblast_level_only,
            $use_cache
        );
    }
}
