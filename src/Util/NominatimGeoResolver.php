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
     * @param  CacheInterface|null  $cache           Optional cache for caching API responses
     * @param  string|null          $locations_path  Optional path to the locations.json file
     * @param  NominatimClient|null $nominatim       Optional Nominatim client instance
     *
     * @throws \RuntimeException If the locations file cannot be read or decoded
     */
    public function __construct(?CacheInterface $cache = null, ?string $locations_path = null, ?NominatimClient $nominatim = null)
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

        $this->nominatim = $nominatim ?? new NominatimClient(null, $cache);
    }

    /**
     * Finds a location UID by geographic coordinates asynchronously
     *
     * @param  float  $lat  Latitude of the coordinates
     * @param  float  $lon  Longitude of the coordinates
     * @return PromiseInterface Promise that resolves to array{uid: int, name: string, matched_by: string}|null matched location info or null if not found
     */
    public function findByCoordinatesAsync(float $lat, float $lon) : PromiseInterface
    {
        // Step 1: High precision reverse lookup to get the exact object
        return $this->nominatim->reverse($lat, $lon, [
            'zoom' => 18,
            'addressdetails' => 1,
            'accept-language' => 'uk'
        ])->then(function (?Place $place) {
            if (! $place || ! $place->getOsmId() || ! $place->getOsmType()) {
                return null;
            }

            // Step 2: Get detailed hierarchy for this object to find parent administrative boundaries
            /** @var string $osm_type */
            $osm_type = $place->getOsmType();

            return $this->nominatim->details([
                'osmtype' => strtoupper(substr($osm_type, 0, 1)), // N, W, R (Must be uppercase)
                'osmid' => $place->getOsmId(),
                'addressdetails' => 1,
                'accept-language' => 'uk'
            ])->then(function (Place $details_place) {
                return $this->matchByAddressHierarchy($details_place);
            });
        });
    }

    /**
     * Matches a location by checking the address hierarchy from specific to general
     *
     * @param  Place  $place  Detailed place object with address components
     * @return array{uid: int, name: string, district_id: int|null, oblast_id: int|null, matched_by: string}|null The matched location data or null if not found
     */
    private function matchByAddressHierarchy(Place $place) : ?array
    {
        // 1. Check direct match
        $match = $this->matchByOsmId($place->getOsmId());
        if ($match) {
            return $match;
        }

        // 2. Check address components
        $components = $place->getAddressComponents();

        // Sort by rank_address descending (highest rank = most specific, e.g. House > City > State)
        usort($components, fn($a, $b) => $b->getRankAddress() <=> $a->getRankAddress());

        foreach ($components as $component) {
            $osm_id = $component->getOsmId();
            if (! $osm_id) {
                continue;
            }

            $match = $this->matchByOsmId($osm_id);
            if ($match) {
                // Enrich match info with source details
                $match['matched_by'] = 'hierarchy_rank_' . $component->getRankAddress();
                return $match;
            }
        }

        return null;
    }

    /**
     * Matches an OSM ID against the local database
     * 
     * @param  int|null  $osm_id  The OpenStreetMap ID to match
     * @return array{uid: int, name: string, district_id: int|null, oblast_id: int|null, matched_by: string}|null Matched location data or null if not found
     */
    private function matchByOsmId(?int $osm_id) : ?array
    {
        if (! $osm_id) return null;

        foreach ($this->locations as $uid => $location) {
            if (isset($location['osm_id']) && (int) $location['osm_id'] === $osm_id) {
                return [
                    'uid'         => (int) $uid,
                    'name'        => $location['name'],
                    'district_id' => $location['district_id'] ?? null,
                    'oblast_id'   => $location['oblast_id'] ?? null,
                    'matched_by'  => 'osm_id',
                ];
            }
        }
        return null;
    }

    /**
     * Retrieves the loaded locations array
     *
     * @return array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}> Map of UID to location details
     */
    public function getLocations() : array
    {
        return $this->locations;
    }

    /**
     * Sets the minimum interval between requests for the rate limiter
     *
     * @param  int  $seconds  Minimum interval in seconds
     * @return void
     */
    public function setRateLimitInterval(int $seconds) : void
    {
        $this->nominatim->setRateLimitInterval($seconds);
    }
}
