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

    public function testConstructor(): void
    {
        $generator = new MappingGenerator(__DIR__ . '/../../../src/Model/locations.json', $this->tempOutput);
        $this->assertInstanceOf(MappingGenerator::class, $generator);
    }

    public function testGenerateWithInvalidLocationsPath(): void
    {
        $generator = new MappingGenerator('/invalid/path/locations.json', $this->tempOutput);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read locations.json');
        $generator->generate();
    }

    public function testGenerateWithInvalidJson(): void
    {
        $invalidJsonPath = sys_get_temp_dir() . '/invalid_locations.json';
        file_put_contents($invalidJsonPath, 'invalid json');
        $generator = new MappingGenerator($invalidJsonPath, $this->tempOutput);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to load locations.json');
        $generator->generate();
        unlink($invalidJsonPath);
    }

    public function testGenerateWithNonStringValue(): void
    {
        $nonStringJsonPath = sys_get_temp_dir() . '/non_string_locations.json';
        $locations = [
            1 => 'Valid String',
            2 => 123, // Non-string value
            3 => 'Another Valid'
        ];
        file_put_contents($nonStringJsonPath, json_encode($locations));
        $generator = new MappingGenerator($nonStringJsonPath, $this->tempOutput);

        $generator->generate();

        $this->assertFileExists($this->tempOutput);

        $content = file_get_contents($this->tempOutput);
        $this->assertNotFalse($content);

        $mapping = json_decode($content, true);
        $this->assertIsArray($mapping);
        // Should have 2 entries (skipped the non-string)
        $this->assertCount(2, $mapping);
        $this->assertArrayHasKey('valid string', $mapping);
        $this->assertArrayHasKey('another valid', $mapping);

        unlink($nonStringJsonPath);
    }
}