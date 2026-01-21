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

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\UserAgent;

class NominatimGeoResolver
{
    /** @var string The base URL for the Nominatim API */
    private string $base_url = 'https://nominatim.openstreetmap.org/reverse';

    /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}> List of locations from the local database */
    private array $locations;

    /** @var SmartCacheManager|null The cache manager instance, or null if caching is disabled */
    private ?SmartCacheManager $cache_manager;

    /**
     * Constructor for NominatimGeoResolver
     *
     * @param  SmartCacheManager|null  $cache_manager  Optional cache manager for caching API responses
     * @param  string|null  $locations_path  Optional path to the locations.json file
     *
     * @throws \RuntimeException If the locations file cannot be read or decoded
     */
    public function __construct(?SmartCacheManager $cache_manager = null, ?string $locations_path = null)
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
        $this->cache_manager = $cache_manager;
    }

    /**
     * Finds a location UID by geographic coordinates
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null The matched location data or null if not found
     */
    public function findByCoordinates(float $lat, float $lon) : ?array
    {
        $cache_key = sprintf('geo_%s_%s', number_format($lat, 4), number_format($lon, 4));

        if ($this->cache_manager && $cached = $this->cache_manager->getCachedData($cache_key)) {
            /** @var array{uid: int, name: string, matched_by: string, similarity?: float}|null $cached */
            return $cached;
        }

        // Strategy: Hierarchical reverse geocoding by zoom levels
        // Zoom 10 usually gives municipalities/cities (hromadas)
        // Zoom 8 usually gives districts
        // Zoom 5 usually gives states/oblasts
        $zoom_levels = [10, 8, 5];
        $nominatim_data = null;

        foreach ($zoom_levels as $zoom) {
            $nominatim_data = $this->reverseGeocode($lat, $lon, $zoom);
            if (! $nominatim_data) {
                continue;
            }

            $result = $this->matchByOsmId($nominatim_data, $zoom);
            if ($result) {
                if ($this->cache_manager) {
                    $this->cache_manager->storeProcessedData($cache_key, $result);
                }
                return $result;
            }
        }

        return null;
    }

    /**
     * Performs a reverse geocoding request to Nominatim API
     *
     * @param  float  $lat  Latitude
     * @param  float  $lon  Longitude
     * @param  int  $zoom  Zoom level for result granularity
     * @return array<string, mixed>|null The decoded JSON response or null on failure
     */
    protected function reverseGeocode(float $lat, float $lon, int $zoom = 18) : ?array
    {
        $params = [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'addressdetails' => 1,
            'accept-language' => 'uk',
            'zoom' => $zoom
        ];

        $url = $this->base_url . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: " . UserAgent::getUserAgent() . "\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }
        $decoded = json_decode($response, true);
        /** @var array<string, mixed>|null $result */
        $result = is_array($decoded) ? $decoded : null;
        return $result;
    }

    /**
     * Matches Nominatim response data to a local location by OSM ID
     *
     * @param  array<string, mixed>  $nominatim_data  Data from Nominatim API
     * @param  int  $zoom  The zoom level used for the request
     * @return array{uid: int, name: string, matched_by: string}|null Matched location info or null
     */
    private function matchByOsmId(array $nominatim_data, int $zoom) : ?array
    {
        $osm_id = $nominatim_data['osm_id'] ?? null;
        if (! is_numeric($osm_id)) {
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
