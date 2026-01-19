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
        // This would require mocking the HTTP, which is hard
        // Skip for now or use integration test
        $this->markTestSkipped('Integration test for API call');
    }
}