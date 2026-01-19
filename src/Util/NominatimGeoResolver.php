<?php

namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Psr\SimpleCache\CacheInterface;

class NominatimGeoResolver
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org/reverse';
    private const CACHE_TTL = 86400;

    private array $locations;
    private array $nameMapping;
    private ?CacheInterface $cache;
    private string $userAgent;

    public function __construct(?string $mappingPath = null, ?CacheInterface $cache = null, string $userAgent = 'alerts-in-ua-php/2.0')
    {
        $this->locations = json_decode(
            file_get_contents(__DIR__ . '/../Model/locations.json'),
            true
        );

        if ($mappingPath === null) {
            $mappingPath = __DIR__ . '/../Model/name_mapping.json';
        }

        if ($mappingPath && file_exists($mappingPath)) {
            $this->nameMapping = json_decode(file_get_contents($mappingPath), true);
        } else {
            $this->nameMapping = $this->generateRuntimeMapping();
        }

        $this->cache = $cache;
        $this->userAgent = $userAgent;
    }

    public function findByCoordinates(float $lat, float $lon): ?array
    {
        $cacheKey = sprintf('geo_%f_%f.json', $lat, $lon);
        $cached = $this->getFromCache($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $nominatimResult = $this->reverseGeocode($lat, $lon);

        if (!$nominatimResult) {
            return null;
        }

        $result = $this->mapToLocation($nominatimResult);

        if ($result !== null) {
            $this->saveToCache($cacheKey, $result);
        }

        return $result;
    }

    private function reverseGeocode(float $lat, float $lon): ?array
    {
        $params = [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'accept-language' => 'uk,en',
            'zoom' => '10'
        ];

        $url = self::BASE_URL . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: {$this->userAgent}\r\n"
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        return json_decode($response, true);
    }

    private function mapToLocation(array $nominatimData): ?array
    {
        $address = $nominatimData['address'] ?? [];

        $candidates = [
            $address['municipality'] ?? null,
            $address['city'] ?? null,
            $address['district'] ?? null,
            $address['state'] ?? null,
            $address['region'] ?? null,
            $address['county'] ?? null
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }

            $result = $this->findUkrainianLocation($candidate);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    private function findUkrainianLocation(string $englishName): ?array
    {
        $normalizedCandidate = TransliterationHelper::normalizeForMatching($englishName);

        if (isset($this->nameMapping[$normalizedCandidate])) {
            $entry = $this->nameMapping[$normalizedCandidate];
            return [
                'uid' => $entry['uid'],
                'name' => $entry['ukrainian'],
                'matched_by' => 'exact'
            ];
        }

        return $this->findFuzzyMatch($normalizedCandidate);
    }

    private function findFuzzyMatch(string $normalizedCandidate): ?array
    {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($this->locations as $uid => $ukrainianName) {
            $normalizedUkrainian = TransliterationHelper::normalizeForMatching($ukrainianName);

            $score = similar_text($normalizedCandidate, $normalizedUkrainian, $percent);
            $similarity = $percent / 100;

            if ($similarity > 0.7 && $similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = [
                    'uid' => (int)$uid,
                    'name' => $ukrainianName,
                    'matched_by' => 'fuzzy',
                    'similarity' => $similarity
                ];
            }
        }

        return $bestMatch;
    }

    private function generateRuntimeMapping(): array
    {
        $mapping = [];

        foreach ($this->locations as $uid => $name) {
            $latin = TransliterationHelper::normalizeForMatching($name);
            $mapping[$latin] = ['uid' => (int)$uid, 'name' => $name];
        }

        return $mapping;
    }

    private function getFromCache(string $key): ?array
    {
        if (!$this->cache) {
            return null;
        }

        try {
            return $this->cache->get($key);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function saveToCache(string $key, array $data): void
    {
        if ($this->cache) {
            try {
                $this->cache->set($key, $data, self::CACHE_TTL);
            } catch (\Throwable $e) {
                // Ignore cache errors
            }
        }
    }
}
