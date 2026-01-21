<?php

namespace Fyennyi\AlertsInUa\Client;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\SimpleCache\CacheInterface;

trait GeoLocationTrait
{
    /** @var NominatimGeoResolver|null Instance of the geo resolver */
    private ?NominatimGeoResolver $geo_resolver = null;

    /**
     * Retrieves alerts for coordinates asynchronously
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  string  $period  Time period for history (default: 'week_ago')
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an Alerts object
     *
     * @throws InvalidParameterException If location not found for coordinates
     */
    public function getAlertsByCoordinatesAsync(float $lat, float $lon, string $period = 'week_ago', bool $use_cache = false) : PromiseInterface
    {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new NominatimGeoResolver($this->cache_manager ?? null, null);
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

    /**
     * Retrieves air raid alert status for coordinates asynchronously
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  bool  $oblast_level_only  Whether to return only oblast-level alerts
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an AirRaidAlertOblastStatus object
     *
     * @throws InvalidParameterException If location not found for coordinates
     */
    public function getAirRaidAlertStatusByCoordinatesAsync(float $lat,float $lon,bool $oblast_level_only = false,bool $use_cache = false) : PromiseInterface
    {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new NominatimGeoResolver($this->cache_manager ?? null, null);
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
