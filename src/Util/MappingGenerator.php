<?php

namespace Fyennyi\AlertsInUa\Util;

class MappingGenerator
{
    public function __construct(
        private readonly string $locationsPath,
        private readonly string $outputPath
    ) {
    }

    public function generate(): void
    {
        $content = @file_get_contents($this->locationsPath);
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations.json');
        }
        $locations = json_decode($content, true);

        if (! is_array($locations)) {
            throw new \RuntimeException('Failed to load locations.json');
        }

        $mapping = [];
        $stats = ['total' => count($locations), 'mapped' => 0];

        foreach ($locations as $uid => $ukrainian_name) {
            if (! is_string($ukrainian_name)) {
                continue;
            }
            $latin = TransliterationHelper::ukrainianToLatin($ukrainian_name);
            $normalized = TransliterationHelper::normalizeForMatching($ukrainian_name);

            $mapping[$normalized] = [
                'uid' => (int)$uid,
                'ukrainian' => $ukrainian_name,
                'latin' => $latin,
                'normalized' => $normalized
            ];

            $stats['mapped']++;
        }

        file_put_contents(
            $this->outputPath,
            json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
