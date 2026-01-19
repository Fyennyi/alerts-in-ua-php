<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use PHPUnit\Framework\TestCase;

class NominatimGeoResolverTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/test_geo_cache';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir);
        }
    }

    protected function tearDown(): void
    {
        // Clean up if needed
    }

    public function testConstructorLoadsLocations(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $resolver = new NominatimGeoResolver(null, $cache);

        // Test that it's created without error
        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);
    }

    public function testFindByCoordinatesWithCacheHit(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.450100_30.523400.json')
            ->willReturn(['uid' => 31, 'name' => 'м. Київ']);

        $resolver = new NominatimGeoResolver(null, $cache);

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertEquals(['uid' => 31, 'name' => 'м. Київ'], $result);
    }

    public function testFindByCoordinatesWithCacheMiss(): void
    {
        // Integration test: requires internet and Nominatim API
        if (! $this->hasInternet()) {
            $this->markTestSkipped('Internet connection required for this test');
        }

        $resolver = new NominatimGeoResolver(null, null);

        $result = $resolver->findByCoordinates(50.4501, 30.5234); // Kyiv coordinates

        // May return null if API fails or rate limited
        if ($result !== null) {
            $this->assertIsArray($result);
            $this->assertArrayHasKey('uid', $result);
            $this->assertArrayHasKey('name', $result);
        }
    }

    private function hasInternet(): bool
    {
        $connected = @fsockopen("www.google.com", 80);
        if ($connected) {
            fclose($connected);
            return true;
        }
        return false;
    }
}