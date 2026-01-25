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
            $this->geo_resolver = new NominatimGeoResolver($this->cache ?? null, null);
        }

        return $this->geo_resolver->findByCoordinatesAsync($lat, $lon)->then(
            function (?array $location) use ($lat, $lon, $period, $use_cache) {
                if ($location === null || ! isset($location['uid'])) {
                    throw new InvalidParameterException(
                        sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
                    );
                }

                /** @var int|string $uid */
                $uid = $location['uid'];
                return $this->getAlertsHistoryAsync($uid, $period, $use_cache);
            }
        );
    }

    /**
     * Retrieves air raid alert status for coordinates using the bulk status endpoint asynchronously
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  bool  $use_cache  Whether to use cached results if available
     * @return PromiseInterface Promise that resolves to an AirRaidAlertStatus object
     *
     * @throws InvalidParameterException If location not found for coordinates
     */
    public function getAirRaidAlertStatusByCoordinatesFromAllAsync(float $lat, float $lon, bool $use_cache = false) : PromiseInterface
    {
        if (! isset($this->geo_resolver)) {
            $this->geo_resolver = new NominatimGeoResolver($this->cache ?? null, null);
        }

        return $this->geo_resolver->findByCoordinatesAsync($lat, $lon)->then(
            function (?array $location) use ($lat, $lon, $use_cache) {
                if ($location === null || ! isset($location['uid'])) {
                    throw new InvalidParameterException(
                        sprintf('Location not found for coordinates: %.4f, %.4f', $lat, $lon)
                    );
                }

                return $this->getAirRaidAlertStatusesAsync($use_cache)->then(
                    function (\Fyennyi\AlertsInUa\Model\AirRaidAlertStatuses $statuses) use ($location) {
                        /** @var array{uid: int, name: string, district_id: int|null, oblast_id: int|null} $location */
                        $uid = (int) $location['uid'];
                        $status = $statuses->getStatus($uid);

                        // If not found by direct UID (common for hromadas), try district_id
                        if ($status === null && isset($location['district_id'])) {
                            $status = $statuses->getStatus((int) $location['district_id']);
                        }

                        // If still not found, try oblast_id
                        if ($status === null && isset($location['oblast_id'])) {
                            $status = $statuses->getStatus((int) $location['oblast_id']);
                        }

                        if ($status === null) {
                            throw new InvalidParameterException(
                                sprintf('Status not available for location: %s (UID: %d)', (string) $location['name'], $uid)
                            );
                        }

                        return $status;
                    }
                );
            }
        );
    }
}
