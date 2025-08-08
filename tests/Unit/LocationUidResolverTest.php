<?php

namespace Fyennyi\AlertsInUa\Tests\Unit;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use PHPUnit\Framework\TestCase;

class LocationUidResolverTest extends TestCase
{
    private string $locationsPath;
    private string $backupPath;

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        unlink($this->locationsPath);
        if (file_exists($this->backupPath)) {
            rename($this->backupPath, $this->locationsPath);
        }
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
}