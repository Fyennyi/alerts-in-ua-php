<?php

require __DIR__ . '/../vendor/autoload.php';

use Fyennyi\AlertsInUa\Util\MappingGenerator;

$locationsPath = __DIR__ . '/../src/Model/locations.json';
$outputPath = __DIR__ . '/../src/Model/name_mapping.json';

try {
    $generator = new MappingGenerator($locationsPath, $outputPath);
    $generator->generate();
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}