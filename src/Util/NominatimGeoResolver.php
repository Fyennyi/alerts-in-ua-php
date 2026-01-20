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
        $this->locations = $decoded;
        $this->cache_manager = $cache_manager;
    }

    public function findByCoordinates(float $lat, float $lon): ?array
    {
        $cache_key = sprintf('geo_%s_%s', number_format($lat, 4), number_format($lon, 4));

        if ($this->cache_manager && $cached = $this->cache_manager->getCachedData($cache_key)) {
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
        return $response ? json_decode($response, true) : null;
    }

    private function mapToLocation(array $nominatim_data): ?array
    {
        $address = $nominatim_data['address'] ?? [];

        $nominatimState = $address['state'] ?? $address['city'] ?? null;

        if (!$nominatimState) {
            return null;
        }

        $relevantLocations = $this->filterLocationsByState($nominatimState);

        $candidates = [
            $address['municipality'] ?? null,
            $address['city'] ?? null,
            $address['town'] ?? null,
            $address['village'] ?? null,
            $address['district'] ?? null
        ];

        foreach ($candidates as $candidateName) {
            if (!$candidateName) continue;

            $match = $this->findBestMatchInList($candidateName, $relevantLocations);

            if ($match) {
                return $match;
            }
        }

        return $this->findOblastFallback($nominatimState);
    }

    private function filterLocationsByState(string $nominatimState): array
    {
        $normalizedState = $this->cleanName($nominatimState);
        $filtered = [];

        foreach ($this->locations as $id => $location) {
            $locState = $location['oblast_name'] ?? $location['name'];

            if ($this->isSimilar($normalizedState, $this->cleanName($locState), 85)) {
                $filtered[$id] = $location;
            }
        }

        return $filtered;
    }

    private function findBestMatchInList(string $searchName, array $locationsList): ?array
    {
        $searchClean = $this->cleanName($searchName);
        $bestMatch = null;
        $bestScore = 0;

        foreach ($locationsList as $id => $location) {
            $locationNameClean = $this->cleanName($location['name']);

            if ($searchClean === $locationNameClean) {
                 return [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'exact'
                ];
            }

            similar_text($searchClean, $locationNameClean, $percent);
            $score = $percent / 100;

            if ($score > 0.85 && $score > $bestScore) {
                $bestScore = $score;
                $bestMatch = [
                    'uid' => (int)$id,
                    'name' => $location['name'],
                    'matched_by' => 'fuzzy',
                    'similarity' => $score
                ];
            }
        }

        return $bestMatch;
    }

    private function findOblastFallback(string $nominatimState): ?array
    {
        $cleanState = $this->cleanName($nominatimState);

        foreach ($this->locations as $id => $location) {
            if (!in_array($location['type'], ['oblast', 'standalone'])) {
                continue;
            }

            if ($this->isSimilar($cleanState, $this->cleanName($location['name']), 80)) {
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
            'територіальна громада', 'сільська', 'селищна', 'міська',
            'район', 'область', 'автономна республіка', 'республіка',
            'м.', 'с.', 'смт.', 'селище', 'місто'
        ];

        $name = str_replace($remove, '', $name);

        $name = preg_replace('/\(.*?\)/', '', $name);

        return trim(preg_replace('/[^\p{L}0-9]+/u', ' ', $name));
    }

    private function isSimilar(string $str1, string $str2, float $thresholdPercent): bool
    {
        similar_text($str1, $str2, $percent);
        return $percent >= $thresholdPercent;
    }
}
