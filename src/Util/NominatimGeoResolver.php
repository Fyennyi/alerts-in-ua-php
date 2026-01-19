<?php

namespace Fyennyi\AlertsInUa\Util;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Psr\SimpleCache\CacheInterface;

class NominatimGeoResolver
{
    private const BASE_URL = 'https://nominatim.openstreetmap.org/reverse';
    private const CACHE_TTL = 86400;

    /** @var array<int, string> */
    private array $locations;

    /** @var array<string, array<string, mixed>> */
    private array $nameMapping;
    private ?CacheInterface $cache;
    private string $userAgent;

    public function __construct(?string $mappingPath = null, ?CacheInterface $cache = null, string $userAgent = 'alerts-in-ua-php/2.0')
    {
        $content = file_get_contents(__DIR__ . '/../Model/locations.json');
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations.json');
        }
        $this->locations = json_decode($content, true);
        if (!is_array($this->locations)) {
            throw new \RuntimeException('Invalid locations.json');
        }

        if ($mappingPath === null) {
            $mappingPath = __DIR__ . '/../Model/name_mapping.json';
        }

        if ($mappingPath && file_exists($mappingPath)) {
            $content = file_get_contents($mappingPath);
            if ($content === false) {
                throw new \RuntimeException("Failed to read {$mappingPath}");
            }
            $this->nameMapping = json_decode($content, true);
            if (!is_array($this->nameMapping)) {
                throw new \RuntimeException("Invalid {$mappingPath}");
            }
        } else {
            $this->nameMapping = $this->generateRuntimeMapping();
        }

        $this->cache = $cache;
        $this->userAgent = $userAgent;
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

        if (!$nominatim_result) {
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

    /**
     * @param  array<string, mixed>  $nominatim_data
     * @return array<string, mixed>|null
     */
    private function mapToLocation(array $nominatim_data): ?array
    {
        $rawAddress = $nominatim_data['address'] ?? null;
        if (!is_array($rawAddress)) {
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

    /**
     * @return array<string, mixed>|null
     */
    private function findUkrainianLocation(string $english_name): ?array
    {
        $normalized_candidate = TransliterationHelper::normalizeForMatching($english_name);

        if (isset($this->nameMapping[$normalized_candidate])) {
            /** @var array{uid: int, ukrainian: string, latin: string, normalized: string} $entry */
            $entry = $this->nameMapping[$normalized_candidate];
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
            $mapping[$latin] = ['uid' => (int)$uid, 'name' => $name];
        }

        return $mapping;
    }

    /**
     * @return array<string, mixed>|null
     */
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

    /**
     * @param array<string, mixed> $data
     */
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
