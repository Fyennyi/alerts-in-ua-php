<?php

namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Util\UserAgent;

class NominatimGeoResolver
{
    private string $baseUrl;

    /** @var array<int, string> */
    private array $locations;

    /** @var array<string, array<string, mixed>> */
    private array $name_mapping;
    private ?SmartCacheManager $cache_manager;

    public function __construct(?string $mapping_path = null, ?SmartCacheManager $cache_manager = null, ?string $locations_path = null)
    {
        $this->baseUrl = 'https://nominatim.openstreetmap.org/reverse';

        if ($locations_path === null) {
            $locations_path = __DIR__ . '/../Model/locations.json';
        }

        $content = file_get_contents($locations_path);
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations.json');
        }
        $decoded = json_decode($content, true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Invalid locations.json');
        }
        /** @var array<int, string> $decoded */
        $this->locations = $decoded;

        if ($mapping_path === null) {
            $mapping_path = __DIR__ . '/../Model/name_mapping.json';
        }

        if ($mapping_path && file_exists($mapping_path)) {
            $content = file_get_contents($mapping_path);
            if ($content === false) {
                throw new \RuntimeException("Failed to read {$mapping_path}");
            }
            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                throw new \RuntimeException("Invalid {$mapping_path}");
            }
            /** @var array<string, array<string, mixed>> $decoded */
            $this->name_mapping = $decoded;
        } else {
            $this->name_mapping = $this->generateRuntimeMapping();
        }

        $this->cache_manager = $cache_manager;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByCoordinates(float $lat, float $lon): ?array
    {
        $cache_key = sprintf('geo_%f_%f.json', $lat, $lon);
        $cached = $this->getFromCache($cache_key);

        if ($cached !== null) {
            return $cached;
        }

        $nominatim_result = $this->reverseGeocode($lat, $lon);

        if (! $nominatim_result) {
            return null;
        }

        $result = $this->mapToLocation($nominatim_result);

        if ($result !== null) {
            $this->saveToCache($cache_key, $result);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function reverseGeocode(float $lat, float $lon): ?array
    {
        $params = [
            'format' => 'json',
            'lat' => $lat,
            'lon' => $lon,
            'accept-language' => 'uk,en',
            'zoom' => '10'
        ];

        $url = $this->baseUrl . '?' . http_build_query($params);

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

        $data = json_decode($response, true);
        /** @var array<string, mixed>|null $data */
        return $data;
    }

    /**
     * @param  array<string, mixed>  $nominatim_data
     * @return array<string, mixed>|null
     */
    private function mapToLocation(array $nominatim_data): ?array
    {
        $rawAddress = $nominatim_data['address'] ?? null;
        if (! is_array($rawAddress)) {
            return null;
        }
        /** @var array<string, mixed> $rawAddress */
        $address = $rawAddress;

        $candidates = [
            $address['municipality'] ?? null,
            $address['city'] ?? null,
            $address['district'] ?? null,
            $address['state'] ?? null,
            $address['region'] ?? null,
            $address['county'] ?? null
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === null || $candidate === '' || ! is_string($candidate)) {
                continue;
            }

            $result = $this->findUkrainianLocation($candidate);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findUkrainianLocation(string $english_name): ?array
    {
        $normalized_candidate = TransliterationHelper::normalizeForMatching($english_name);

        if (isset($this->name_mapping[$normalized_candidate])) {
            /** @var array{uid: int, ukrainian: string, latin: string, normalized: string} $entry */
            $entry = $this->name_mapping[$normalized_candidate];
            return [
                'uid' => $entry['uid'],
                'name' => $entry['ukrainian'],
                'matched_by' => 'exact'
            ];
        }

        return $this->findFuzzyMatch($normalized_candidate);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findFuzzyMatch(string $normalized_candidate): ?array
    {
        $best_match = null;
        $best_score = 0;

        foreach ($this->locations as $uid => $ukrainian_name) {
            $normalized_ukrainian = TransliterationHelper::normalizeForMatching($ukrainian_name);

            $score = similar_text($normalized_candidate, $normalized_ukrainian, $percent);
            $similarity = $percent / 100;

            if ($similarity > 0.7 && $similarity > $best_score) {
                $best_score = $similarity;
                $best_match = [
                    'uid' => (int)$uid,
                    'name' => $ukrainian_name,
                    'matched_by' => 'fuzzy',
                    'similarity' => $similarity
                ];
            }
        }

        return $best_match;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function generateRuntimeMapping(): array
    {
        $mapping = [];

        foreach ($this->locations as $uid => $name) {
            $latin = TransliterationHelper::normalizeForMatching($name);
            $mapping[$latin] = ['uid' => (int)$uid, 'ukrainian' => $name, 'latin' => $latin, 'normalized' => $latin];
        }

        return $mapping;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getFromCache(string $key): ?array
    {
        if (! $this->cache_manager) {
            return null;
        }

        try {
            $data = $this->cache_manager->getCachedData($key);
            /** @var array<string, mixed>|null $data */
            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function saveToCache(string $key, array $data): void
    {
        if ($this->cache_manager) {
            try {
                $this->cache_manager->storeProcessedData($key, $data);
            } catch (\Throwable $e) {
                // Ignore cache errors
            }
        }
    }
}
