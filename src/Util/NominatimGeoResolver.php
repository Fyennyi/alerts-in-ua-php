<?php

namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\UserAgent;

class NominatimGeoResolver
{
    private string $base_url;

    /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}> */
    private array $locations;

    private ?SmartCacheManager $cache_manager;

    public function __construct(?SmartCacheManager $cache_manager = null, ?string $locations_path = null)
    {
        $this->base_url = 'https://nominatim.openstreetmap.org/reverse';

        if ($locations_path === null) {
            $locations_path = __DIR__ . '/../Model/locations_with_hierarchy.json';
        }

        $content = @file_get_contents($locations_path);
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations_with_hierarchy.json');
        }
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid locations_with_hierarchy.json structure');
        }
        /** @var array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}> $decoded */
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

        $nominatim_result = $this->reverseGeocode($lat, $lon);

        if (! $nominatim_result) {
            return null;
        }

        $result = $this->mapToLocation($nominatim_result);

        if ($result !== null && $this->cache_manager) {
            $this->cache_manager->storeProcessedData($cache_key, $result);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<string, mixed>|null
     */
    private function reverseGeocode(float $lat, float $lon): ?array
    {
        $params = [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'addressdetails' => 1,
            'accept-language' => 'uk',
            'zoom' => 18
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
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null
     */
    private function mapToLocation(array $nominatim_data): ?array
    {
        $address = $nominatim_data['address'] ?? [];

        if (! is_array($address)) {
            return null;
        }

        $nominatim_state = $address['state'] ?? $address['city'] ?? null;

        if (! is_string($nominatim_state) || $nominatim_state === '') {
            return null;
        }

        $relevant_locations = $this->filterLocationsByState($nominatim_state);

        $candidates = [
            $address['municipality'] ?? null,
            $address['city'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            $address['district'] ?? null
        ];

        foreach ($candidates as $candidate_name) {
            if (! is_string($candidate_name) || $candidate_name === '') {
                continue;
            }

            $match = $this->findBestMatchInList($candidate_name, $relevant_locations);

            if ($match) {
                return $match;
            }
        }

        $fallback_match = $this->findFuzzyGlobal($nominatim_state);
        if ($fallback_match) {
            return $fallback_match;
        }

        return $this->findOblastFallback($nominatim_state);
    }

    /**
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null
     */
    private function findFuzzyGlobal(string $search_name): ?array
    {
        $search_clean = $this->cleanName($search_name);
        $best_match = null;
        $best_score = 0.0;

        foreach ($this->locations as $id => $location) {
            if (! isset($location['name']) || ! is_string($location['name'])) {
                continue;
            }

            $location_name_clean = $this->cleanName($location['name']);

            if ($search_clean === $location_name_clean) {
                 return [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'global_exact'
                ];
            }

            similar_text($search_clean, $location_name_clean, $percent);
            $score = $percent / 100;

            if ($score > 0.80 && $score > $best_score) {
                $best_score = $score;
                $best_match = [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'global_fuzzy',
                    'similarity' => $score
                ];
            }
        }

        return $best_match;
    }

    /**
     * @return array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}>
     */
    private function filterLocationsByState(string $nominatim_state): array
    {
        $normalized_state = $this->cleanName($nominatim_state);
        $filtered = [];

        foreach ($this->locations as $id => $location) {
            $loc_state = $location['oblast_name'] ?? $location['name'];

            if ($this->isSimilar($normalized_state, $this->cleanName($loc_state), 85)) {
                $filtered[$id] = $location;
            }
        }

        return $filtered;
    }

    /**
     * @param array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}> $locations_list
     * @return array{uid: int, name: string, matched_by: string, similarity?: float}|null
     */
    private function findBestMatchInList(string $search_name, array $locations_list): ?array
    {
        $search_clean = $this->cleanName($search_name);
        $best_match = null;
        $best_score = 0.0;

        foreach ($locations_list as $id => $location) {
            if (! isset($location['name']) || ! is_string($location['name'])) {
                continue;
            }

            $location_name_clean = $this->cleanName($location['name']);

            if ($search_clean === $location_name_clean) {
                 return [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'exact'
                ];
            }

            similar_text($search_clean, $location_name_clean, $percent);
            $score = $percent / 100;

            if ($score > 0.85 && $score > $best_score) {
                $best_score = $score;
                $best_match = [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'fuzzy',
                    'similarity' => $score
                ];
            }
        }

        return $best_match;
    }

    /**
     * @return array{uid: int, name: string, matched_by: string}|null
     */
    private function findOblastFallback(string $nominatim_state): ?array
    {
        $clean_state = $this->cleanName($nominatim_state);

        foreach ($this->locations as $id => $location) {
            if (! isset($location['type']) || ! in_array($location['type'], ['oblast', 'standalone'], true)) {
                continue;
            }

            if (! isset($location['name']) || ! is_string($location['name'])) {
                continue;
            }

            if ($this->isSimilar($clean_state, $this->cleanName($location['name']), 80)) {
                 return [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'oblast_fallback'
                ];
            }
        }
        return null;
    }

    private function cleanName(string $name): string
    {
        $name = mb_strtolower($name);

        $remove = [
            'територіальна громада', 'сільська', 'селищна', 'міська', 'громада',
            'район', 'область', 'автономна республіка', 'республіка',
            'м.', 'с.', 'смт.', 'селище', 'місто'
        ];

        $name = str_replace($remove, '', $name);
        $name = (string) preg_replace('/\(.*?\)/', '', $name);
        $trimmed = (string) preg_replace('/[^\p{L}0-9]+/u', ' ', $name);
        $trimmed = trim($trimmed);

        return $trimmed;
    }

    public function isSimilar(string $str1, string $str2, float $threshold_percent): bool
    {
        similar_text($str1, $str2, $percent);
        return $percent >= $threshold_percent;
    }

    /**
     * @return array<int, array{name: string, type: string, oblast_id: int, oblast_name: string|null, district_id: int|null, district_name: string|null}>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }
}
