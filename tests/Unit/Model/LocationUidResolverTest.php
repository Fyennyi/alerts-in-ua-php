<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use PHPUnit\Framework\TestCase;

class LocationUidResolverTest extends TestCase
{
    private string $locationsPath;

    private string $backupPath;

    protected function setUp() : void
    {
        $this->locationsPath = __DIR__ . '/../../../src/Model/locations.json';
        $this->backupPath = $this->locationsPath . '.bak';

        if (file_exists($this->locationsPath)) {
            rename($this->locationsPath, $this->backupPath);
        }

        $testLocations = [
            31 => ['name' => 'м. Київ', 'type' => 'standalone', 'oblast_id' => 31, 'oblast_name' => 'м. Київ'],
            22 => ['name' => 'Харківська область', 'type' => 'oblast', 'oblast_id' => 22, 'oblast_name' => 'Харківська область'],
        ];
        file_put_contents($this->locationsPath, json_encode($testLocations));
    }

    protected function tearDown() : void
    {
        if (file_exists($this->locationsPath)) {
            chmod($this->locationsPath, 0644);
            unlink($this->locationsPath);
        }
        if (file_exists($this->backupPath)) {
            rename($this->backupPath, $this->locationsPath);
        }
    }

    public function testConstructorThrowsExceptionIfFileCannotBeRead() : void
    {
        file_put_contents($this->locationsPath, json_encode(['test' => ['name' => 'test']]));
        chmod($this->locationsPath, 000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Could not read locations data file from " . realpath(__DIR__ . '/../../../src/Model/locations.json'));

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
        unlink($this->locationsPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Locations data file not found at " . realpath(__DIR__ . '/../../../src/Model/locations.json'));

        new LocationUidResolver();
    }

    public function testConstructorThrowsExceptionIfJsonIsInvalid() : void
    {
        file_put_contents($this->locationsPath, 'this is not valid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Failed to decode locations JSON from " . realpath(__DIR__ . '/../../../src/Model/locations.json'));

        new LocationUidResolver();
    }
}
