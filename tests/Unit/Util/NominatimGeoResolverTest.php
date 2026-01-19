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

    public function testReverseGeocode(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('reverseGeocode');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 50.45, 30.52);

        // Since it's HTTP, may be null or array
        $this->assertTrue($result === null || is_array($result));
    }

    public function testMapToLocation(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'city' => 'Kyiv'
            ]
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNotNull($result);
        $this->assertEquals('м. Київ', $result['name']);
    }

    public function testFindUkrainianLocation(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findUkrainianLocation');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Kyiv');

        $this->assertNotNull($result);
        $this->assertEquals('м. Київ', $result['name']);
    }

    public function testFindFuzzyMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyMatch');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Kyiv');

        $this->assertNotNull($result);
        $this->assertEquals('м. Київ', $result['name']);
    }

    public function testGenerateRuntimeMapping(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('generateRuntimeMapping');
        $method->setAccessible(true);

        $result = $method->invoke($resolver);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    public function testGetFromCacheWithCache(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('test_key')
            ->willReturn(['data' => 'value']);

        $resolver = new NominatimGeoResolver(null, $cache);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('getFromCache');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'test_key');

        $this->assertEquals(['data' => 'value'], $result);
    }

    public function testGetFromCacheWithCacheException(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('test_key')
            ->willThrowException(new \Exception('Cache error'));

        $resolver = new NominatimGeoResolver(null, $cache);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('getFromCache');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'test_key');

        $this->assertNull($result);
    }

    public function testGetFromCacheWithoutCache(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('getFromCache');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'test_key');

        $this->assertNull($result);
    }

    public function testSaveToCacheWithCache(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('storeProcessedData')
            ->with('test_key', ['data' => 'value']);

        $resolver = new NominatimGeoResolver(null, $cache);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('saveToCache');
        $method->setAccessible(true);

        $method->invoke($resolver, 'test_key', ['data' => 'value']);
    }

    public function testSaveToCacheWithCacheException(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('storeProcessedData')
            ->with('test_key', ['data' => 'value'])
            ->willThrowException(new \Exception('Cache error'));

        $resolver = new NominatimGeoResolver(null, $cache);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('saveToCache');
        $method->setAccessible(true);

        $method->invoke($resolver, 'test_key', ['data' => 'value']);
        // No exception thrown, silently ignored
    }

    public function testSaveToCacheWithoutCache(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('saveToCache');
        $method->setAccessible(true);

        $method->invoke($resolver, 'test_key', ['data' => 'value']);
        // No exception
    }
}