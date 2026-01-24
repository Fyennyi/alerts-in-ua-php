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
 
namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\Nominatim\Client as NominatimClient;
use Fyennyi\Nominatim\Model\Place;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\SimpleCache\CacheInterface;

class NominatimGeoResolver
{
    /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}> List of locations from the local database */
    private array $locations;

    /** @var NominatimClient The async Nominatim client */
    private NominatimClient $nominatim;

    /**
     * Constructor for NominatimGeoResolver
     *
     * @param  CacheInterface|null  $cache  Optional cache for caching API responses
     * @param  string|null  $locations_path  Optional path to the locations.json file
     *
     * @throws \RuntimeException If the locations file cannot be read or decoded
     */
    public function __construct(?CacheInterface $cache = null, ?string $locations_path = null)
    {
        if ($locations_path === null) {
            $locations_path = __DIR__ . '/../Model/locations.json';
        }

        $content = @file_get_contents($locations_path);
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations.json');
        }
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid locations.json structure');
        }
        /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}> $decoded */
        $this->locations = $decoded;
        
        $this->nominatim = new NominatimClient(null, $cache);
    }

    /**
     * Finds a location UID by geographic coordinates asynchronously
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @return PromiseInterface Promise that resolves to array{uid: int, name: string, matched_by: string}|null
     */
    public function findByCoordinatesAsync(float $lat, float $lon) : PromiseInterface
    {
        // Strategy: Hierarchical reverse geocoding by zoom levels
        // Zoom 10 usually gives municipalities/cities (hromadas)
        // Zoom 8 usually gives districts
        // Zoom 5 usually gives states/oblasts
        $zoom_levels = [10, 8, 5];
        
        return $this->resolveByZoomLevelsAsync($lat, $lon, $zoom_levels);
    }

    /**
     * Recursive helper to try different zoom levels asynchronously
     * 
     * @param float $lat
     * @param float $lon
     * @param int[] $zooms
     * @return PromiseInterface
     */
    private function resolveByZoomLevelsAsync(float $lat, float $lon, array $zooms) : PromiseInterface
    {
        if (empty($zooms)) {
            return Create::promiseFor(null);
        }

        $zoom = array_shift($zooms);

        return $this->nominatim->reverse($lat, $lon, [
            'zoom' => $zoom,
            'addressdetails' => 1,
            'accept-language' => 'uk'
        ])->then(function (?Place $place) use ($lat, $lon, $zooms, $zoom) {
            if ($place) {
                $result = $this->matchByOsmId($place, $zoom);
                if ($result) {
                    return $result;
                }
            }

            return $this->resolveByZoomLevelsAsync($lat, $lon, $zooms);
        });
    }

    /**
     * Finds a location UID by geographic coordinates (synchronous wrapper)
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null The matched location data or null if not found
     */
    public function findByCoordinates(float $lat, float $lon) : ?array
    {
        return $this->findByCoordinatesAsync($lat, $lon)->wait();
    }

    /**
     * Matches Nominatim response data to a local location by OSM ID
     *
     * @param  Place  $place  Place object from Nominatim API
     * @param  int  $zoom  The zoom level used for the request
     * @return array{uid: int, name: string, matched_by: string}|null Matched location info or null
     */
    private function matchByOsmId(Place $place, int $zoom) : ?array
    {
        $osm_id = $place->getOsmId();
        if ($osm_id === null) {
            return null;
        }

        $osm_id = (int) $osm_id;

        foreach ($this->locations as $uid => $location) {
            if (isset($location['osm_id']) && (int) $location['osm_id'] === $osm_id) {
                return [
                    'uid' => (int) $uid,
                    'name' => $location['name'],
                    'matched_by' => 'osm_id_zoom_' . $zoom
                ];
            }
        }

        return null;
    }

    /**
     * Retrieves the loaded locations array
     *
     * @return array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}>
     */
    public function getLocations() : array
    {
        return $this->locations;
    }
}