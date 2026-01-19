<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
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

    public function testConstructorWithMappingPath(): void
    {
        $mappingPath = sys_get_temp_dir() . '/test_mapping.json';
        file_put_contents($mappingPath, json_encode(['test' => ['uid' => 1, 'ukrainian' => 'Test', 'latin' => 'Test', 'normalized' => 'test']]));

        $cache = $this->createMock(SmartCacheManager::class);
        $resolver = new NominatimGeoResolver($mappingPath, $cache);

        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);

        unlink($mappingPath);
    }

    public function testConstructorWithInvalidMappingPath(): void
    {
        $mappingPath = sys_get_temp_dir() . '/invalid_mapping.json';
        file_put_contents($mappingPath, 'invalid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid ' . $mappingPath);

        new NominatimGeoResolver($mappingPath, null);

        unlink($mappingPath);
    }

    public function testConstructorWithInvalidLocationsPath(): void
    {
        $invalidLocationsPath = sys_get_temp_dir() . '/invalid_locations.json';
        file_put_contents($invalidLocationsPath, 'invalid json');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid locations.json');

        new NominatimGeoResolver(null, null, $invalidLocationsPath);

        unlink($invalidLocationsPath);
    }

    public function testConstructorWithNonExistentLocationsPath(): void
    {
        $nonExistentPath = '/non/existent/path/locations.json';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read locations.json');

        new NominatimGeoResolver(null, null, $nonExistentPath);
    }

    public function testConstructorWithUnreadableMappingPath(): void
    {
        $vfs = vfsStream::setup('root', 0777, [
            'mapping.json' => 'valid json content'
        ]);
        $mappingPath = $vfs->url() . '/mapping.json';

        // Make file unreadable
        chmod($mappingPath, 0000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read ' . $mappingPath);

        try {
            new NominatimGeoResolver($mappingPath, null);
        } finally {
            chmod($mappingPath, 0777); // Restore for cleanup
        }
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

    public function testFindByCoordinatesWithoutCache(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.450100_30.523400.json')
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('storeProcessedData')
            ->with('geo_50.450100_30.523400.json', $this->isArray());

        $resolver = new NominatimGeoResolver(null, $cache);

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('name', $result);
    }

    public function testFindByCoordinatesWithoutCacheNoResult(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_0.000000_0.000000.json')
            ->willReturn(null);
        // No store since result null

        $resolver = new NominatimGeoResolver(null, $cache);

        $result = $resolver->findByCoordinates(0.0, 0.0); // Ocean coordinates

        $this->assertNull($result);
    }

    public function testFindByCoordinatesHttpFailure(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.450100_30.523400.json')
            ->willReturn(null);
        // No store

        $resolver = new NominatimGeoResolver(null, $cache);

        // Set invalid baseUrl
        $reflection = new \ReflectionClass($resolver);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);
        $property->setValue($resolver, 'http://127.0.0.1:9999/reverse');

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function testFindByCoordinatesInvalidJsonResponse(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.450100_30.523400.json')
            ->willReturn(null);
        // No store

        $resolver = new NominatimGeoResolver(null, $cache);

        // Set baseUrl to return HTML
        $reflection = new \ReflectionClass($resolver);
        $property = $reflection->getProperty('baseUrl');
        $property->setAccessible(true);
        $property->setValue($resolver, 'http://httpbin.org/html');

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertNull($result);
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

    public function testMapToLocationWithNoAddress(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            // No address
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNull($result);
    }

    public function testMapToLocationWithNoMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'city' => 'UnknownCity'
            ]
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNull($result);
    }

    public function testMapToLocationWithNonStringCandidate(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'city' => 123, // Non-string candidate
                'municipality' => 'Kyiv' // Valid string
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

    public function testFindUkrainianLocationFuzzyMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findUkrainianLocation');
        $method->setAccessible(true);

        // Use a name that doesn't match exactly but fuzzy matches
        $result = $method->invoke($resolver, 'Kiev'); // Note: Kiev instead of Kyiv

        $this->assertNotNull($result);
        $this->assertEquals('м. Київ', $result['name']);
        $this->assertEquals('fuzzy', $result['matched_by']);
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

    public function testFindFuzzyMatchNoMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyMatch');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'NonExistentCity12345');

        $this->assertNull($result);
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
        $this->assertTrue(true);
    }

    public function testSaveToCacheWithoutCache(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('saveToCache');
        $method->setAccessible(true);

        $method->invoke($resolver, 'test_key', ['data' => 'value']);
        // No exception, silently ignored
        $this->assertTrue(true);
    }
}