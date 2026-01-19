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
        $content = file_get_contents($this->locationsPath);
        if ($content === false) {
            throw new \RuntimeException('Failed to read locations.json');
        }
        $locations = json_decode($content, true);

        if (! is_array($locations)) {
            throw new \RuntimeException('Failed to load locations.json');
        }

        $mapping = [];
        $stats = ['total' => count($locations), 'mapped' => 0];

        echo "Generating name mapping for {$stats['total']} locations...\n";
        echo str_repeat('=', 60) . "\n";

        foreach ($locations as $uid => $ukrainianName) {
            if (!is_string($ukrainianName)) {
                continue;
            }
            $latin = TransliterationHelper::ukrainianToLatin($ukrainianName);
            $normalized = TransliterationHelper::normalizeForMatching($ukrainianName);

            $mapping[$normalized] = [
                'uid' => (int)$uid,
                'ukrainian' => $ukrainianName,
                'latin' => $latin,
                'normalized' => $normalized
            ];

            $stats['mapped']++;

            echo sprintf(
                "%5d: %-35s -> %s\n",
                $uid,
                $ukrainianName,
                $normalized
            );
        }

        echo str_repeat('=', 60) . "\n";
        echo "Mapped: {$stats['mapped']} / {$stats['total']}\n";

        file_put_contents(
            $this->outputPath,
            json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        echo "\nMapping saved to: {$this->outputPath}\n";
    }
}
