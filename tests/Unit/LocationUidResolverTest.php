<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use PHPUnit\Framework\TestCase;

class LocationUidResolverTest extends TestCase
{
    private string $locationsPath;

    private string $backupPath;

    protected function setUp() : void
    {
        $this->locationsPath = __DIR__ . '/../../src/Model/locations.json';
        $this->backupPath = $this->locationsPath . '.bak';

        if (file_exists($this->locationsPath)) {
            rename($this->locationsPath, $this->backupPath);
        }

        $testLocations = [
            31 => 'м. Київ',
            22 => 'Харківська область',
        ];
        file_put_contents($this->locationsPath, json_encode($testLocations));
    }

    protected function tearDown() : void
    {
        if (file_exists($this->locationsPath)) {
            // Restore permissions before unlinking to ensure cleanup works
            chmod($this->locationsPath, 0644);
            unlink($this->locationsPath);
        }
        if (file_exists($this->backupPath)) {
            rename($this->backupPath, $this->locationsPath);
        }
    }

    public function testConstructorThrowsExceptionIfFileCannotBeRead() : void
    {
        // Ensure the file exists and then make it unreadable
        file_put_contents($this->locationsPath, json_encode(['test' => 'data']));
        chmod($this->locationsPath, 000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Could not read locations data file from " . realpath(__DIR__ . '/../../src/Model/locations.json'));

        new LocationUidResolver();
    }

    public function testResolveUid()
    {
        $resolver = new LocationUidResolver();
        $this->assertEquals(31, $resolver->resolveUid('м. Київ'));
        $this->assertEquals(22, $resolver->resolveUid('Харківська область'));
    }

    public function testResolveLocationTitle()
    {
        $resolver = new LocationUidResolver();
        $this->assertEquals('м. Київ', $resolver->resolveLocationTitle(31));
        $this->assertEquals('Харківська область', $resolver->resolveLocationTitle(22));
    }

    public function testResolveUidWithUnknownLocationThrowsException()
    {
        $resolver = new LocationUidResolver();
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown location: Неіснуюча область');
        $resolver->resolveUid('Неіснуюча область');
    }

    public function testResolveLocationTitleWithUnknownUidThrowsException()
    {
        $resolver = new LocationUidResolver();
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown UID: 999');
        $resolver->resolveLocationTitle(999);
    }

    public function testConstructorThrowsExceptionIfLocationsFileNotFound() : void
    {
        unlink($this->locationsPath); // Remove the file to simulate it not being found

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Locations data file not found at " . realpath(__DIR__ . '/../../src/Model/locations.json'));

        new LocationUidResolver();
    }

    /**
     * Test that the constructor throws a RuntimeException if the JSON content is invalid.
     * This specifically targets line 31 in src/Model/LocationUidResolver.php,
     * ensuring that the `!is_array($locations)` condition is met when json_decode fails.
     */
    public function testConstructorThrowsExceptionIfJsonIsInvalid() : void
    {
        // Ensure the locations file exists but contains invalid JSON
        file_put_contents($this->locationsPath, 'this is not valid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to decode locations JSON from " . realpath(__DIR__ . '/../../src/Model/locations.json'));

        new LocationUidResolver();
    }
}
