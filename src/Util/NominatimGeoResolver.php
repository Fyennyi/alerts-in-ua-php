<?php

namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\UserAgent;

class NominatimGeoResolver
{
    private string $base_url;

    /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null, osm_id: int|null}> */
    private array $locations;

    private ?SmartCacheManager $cache_manager;

    public function __construct(?SmartCacheManager $cache_manager = null, ?string $locations_path = null)
    {
        $this->base_url = 'https://nominatim.openstreetmap.org/reverse';

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
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null
     */
    public function findByCoordinates(float $lat, float $lon): ?array
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
     * @return array<string, mixed>|null
     */
    protected function reverseGeocode(float $lat, float $lon, int $zoom = 18): ?array
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
     * @param array<string, mixed> $nominatim_data
     * @return array{uid: int, name: string, matched_by: string}|null
     */
    private function matchByOsmId(array $nominatim_data, int $zoom): ?array
    {
        $osm_id = $nominatim_data['osm_id'] ?? null;
        if (! is_numeric($osm_id)) {
            return null;
        }

        $osm_id = (int)$osm_id;

        foreach ($this->locations as $uid => $location) {
            if (isset($location['osm_id']) && (int)$location['osm_id'] === $osm_id) {
                return [
                    'uid' => (int)$uid,
                    'name' => $location['name'],
                    'matched_by' => 'osm_id_zoom_' . $zoom
                ];
            }
        }

        return null;
    }

    /**
     * @return array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }
}
