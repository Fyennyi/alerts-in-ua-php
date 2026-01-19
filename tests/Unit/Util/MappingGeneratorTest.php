<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Util\MappingGenerator;
use PHPUnit\Framework\TestCase;

class MappingGeneratorTest extends TestCase
{
    private string $tempOutput;

    protected function setUp(): void
    {
        $this->tempOutput = sys_get_temp_dir() . '/test_mapping.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempOutput)) {
            unlink($this->tempOutput);
        }
    }

    public function testGenerateCreatesMappingFile(): void
    {
        $locationsPath = __DIR__ . '/../../../src/Model/locations.json';
        $generator = new MappingGenerator($locationsPath, $this->tempOutput);

        $generator->generate();

        $this->assertFileExists($this->tempOutput);

        $content = file_get_contents($this->tempOutput);
        $this->assertNotFalse($content);

        $mapping = json_decode($content, true);
        $this->assertIsArray($mapping);
        $this->assertNotEmpty($mapping);

        // Check a sample entry
        $this->assertArrayHasKey('kyiv', $mapping);
        $entry = $mapping['kyiv'];
        $this->assertEquals('м. Київ', $entry['ukrainian']);
        $this->assertEquals(31, $entry['uid']);
    }
}