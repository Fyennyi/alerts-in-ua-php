<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class NominatimGeoResolverTest extends TestCase
{
    public function testConstructorLoadsLocations(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $resolver = new NominatimGeoResolver($cache, null);

        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);
    }

    public function testConstructorWithLocationsPath(): void
    {
        $locationsPath = sys_get_temp_dir() . '/test_locations.json';
        file_put_contents($locationsPath, json_encode(['1' => ['name' => 'Test', 'type' => 'hromada', 'oblast_name' => 'Test Oblast']]));

        $cache = $this->createMock(SmartCacheManager::class);
        $resolver = new NominatimGeoResolver($cache, $locationsPath);

        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);

        unlink($locationsPath);
    }

    public function testConstructorWithInvalidLocationsPath(): void
    {
        $invalidLocationsPath = sys_get_temp_dir() . '/invalid_locations.json';
        file_put_contents($invalidLocationsPath, 'invalid json');

        $this->expectException(\RuntimeException::class);

        new NominatimGeoResolver(null, $invalidLocationsPath);

        unlink($invalidLocationsPath);
    }

    public function testConstructorWithNonExistentLocationsPath(): void
    {
        $nonExistentPath = '/non/existent/path/locations.json';

        $this->expectException(\RuntimeException::class);

        new NominatimGeoResolver(null, $nonExistentPath);
    }

    public function testFindByCoordinatesWithCacheHit(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.4501_30.5234')
            ->willReturn(['uid' => 31, 'name' => 'м. Київ']);

        $resolver = new NominatimGeoResolver($cache, null);

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertEquals(['uid' => 31, 'name' => 'м. Київ'], $result);
    }

    public function testFindByCoordinatesWithoutCache(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.4501_30.5234')
            ->willReturn(null);
        $cache->expects($this->once())
            ->method('storeProcessedData')
            ->with('geo_50.4501_30.5234', $this->isArray());

        $resolver = new NominatimGeoResolver($cache, null);

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
            ->with('geo_0.0000_0.0000')
            ->willReturn(null);

        $resolver = new NominatimGeoResolver($cache, null);

        $result = $resolver->findByCoordinates(0.0, 0.0);

        $this->assertNull($result);
    }

    public function testFindByCoordinatesHttpFailure(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->with('geo_50.4501_30.5234')
            ->willReturn(null);

        $resolver = new NominatimGeoResolver($cache, null);

        $reflection = new \ReflectionClass($resolver);
        $property = $reflection->getProperty('base_url');
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
            ->with('geo_50.4501_30.5234')
            ->willReturn(null);

        $resolver = new NominatimGeoResolver($cache, null);

        $reflection = new \ReflectionClass($resolver);
        $property = $reflection->getProperty('base_url');
        $property->setAccessible(true);
        $property->setValue($resolver, 'http://httpbin.org/html');

        $result = $resolver->findByCoordinates(50.4501, 30.5234);

        $this->assertNull($result);
    }

    public function testMapToLocationWithExactMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'city' => 'Вінниця'
            ]
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNotNull($result);
        $this->assertEquals('м. Вінниця та Вінницька територіальна громада', $result['name']);
    }

    public function testMapToLocationWithNoAddress(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNull($result);
    }

    public function testMapToLocationWithInvalidAddress(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => 'not an array'
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNull($result);
    }

    public function testFilterLocationsByState(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('filterLocationsByState');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Вінницька область');

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        foreach ($result as $location) {
            $this->assertEquals('Вінницька область', $location['oblast_name']);
        }
    }

    public function testFindFuzzyGlobal(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyGlobal');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Одеса');

        $this->assertNotNull($result);
        $this->assertEquals('м. Одеса та Одеська територіальна громада', $result['name']);
    }

    public function testFindBestMatchInListFuzzyMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findBestMatchInList');
        $method->setAccessible(true);

        $locations = [
            1 => ['name' => 'Test Hromada Name', 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
        ];

        $result = $method->invoke($resolver, 'Test Hromada Nam', $locations);

        $this->assertNotNull($result);
        $this->assertEquals('Test Hromada Name', $result['name']);
        $this->assertEquals('prefix', $result['matched_by']);
        $this->assertArrayHasKey('similarity', $result);
    }

    public function testFindFuzzyGlobalExactMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyGlobal');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Вінницька область');

        $this->assertNotNull($result);
        $this->assertEquals('Вінницька область', $result['name']);
        $this->assertEquals('global_exact', $result['matched_by']);
    }

    public function testFindFuzzyGlobalFuzzyMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyGlobal');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Луганск');

        $this->assertNotNull($result);
        $this->assertEquals('м. Луганськ та Луганська територіальна громада', $result['name']);
        $this->assertEquals('global_fuzzy', $result['matched_by']);
        $this->assertArrayHasKey('similarity', $result);
    }

    public function testFindFuzzyGlobalNoMatch(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findFuzzyGlobal');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'NonExistentCity12345XYZ');

        $this->assertNull($result);
    }

    public function testFindOblastFallback(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findOblastFallback');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Вінницька область');

        $this->assertNotNull($result);
        $this->assertEquals('Вінницька область', $result['name']);
        $this->assertEquals('oblast_fallback', $result['matched_by']);
    }

    public function testMapToLocationWithOblastFallback(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'state' => 'NonExistentRegionXYZ123456',
                'city' => 'UnknownCityABC789',
            ]
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNull($result);
    }

    public function testFindBestMatchInListWithInvalidLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findBestMatchInList');
        $method->setAccessible(true);

        $locations = [
            1 => ['name' => 'Test Location', 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
            2 => ['type' => 'hromada', 'oblast_name' => 'Test Oblast'],
            3 => ['name' => 123, 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
            4 => ['name' => 'Test Location 2', 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
        ];

        $result = $method->invoke($resolver, 'Test Location 2', $locations);

        $this->assertNotNull($result);
        $this->assertEquals('Test Location 2', $result['name']);
    }

    public function testFindFuzzyGlobalWithInvalidLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);

        $locationsProperty = $reflection->getProperty('locations');
        $locationsProperty->setAccessible(true);
        $locationsProperty->setValue($resolver, [
            1 => ['name' => 123, 'type' => 'hromada', 'oblast_name' => 'Test'],
            2 => ['type' => 'hromada', 'oblast_name' => 'Test'],
            3 => ['name' => 'Вінницька область', 'type' => 'oblast', 'oblast_name' => 'Вінницька область'],
        ]);

        $method = $reflection->getMethod('findFuzzyGlobal');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Вінницька');

        $this->assertNotNull($result);
        $this->assertEquals('Вінницька область', $result['name']);
    }

    public function testFindOblastFallbackWithInvalidLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);

        $locationsProperty = $reflection->getProperty('locations');
        $locationsProperty->setAccessible(true);
        $locationsProperty->setValue($resolver, [
            1 => ['name' => 123, 'type' => 'oblast', 'oblast_name' => 'Test'],
            2 => ['type' => 'oblast', 'oblast_name' => 'Test'],
            3 => ['name' => 'Вінницька область', 'type' => 'oblast', 'oblast_name' => 'Вінницька область'],
        ]);

        $method = $reflection->getMethod('findOblastFallback');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'Вінницька область');

        $this->assertNotNull($result);
        $this->assertEquals('Вінницька область', $result['name']);
    }

    public function testFindOblastFallbackSkipsNonOblastTypes(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findOblastFallback');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'NonExistentOblast');

        $this->assertNull($result);
    }

    public function testCleanName(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('cleanName');
        $method->setAccessible(true);

        $this->assertEquals('вінниця', $method->invoke($resolver, 'м. Вінниця'));
        $this->assertEquals('львівська', $method->invoke($resolver, 'Львівська область'));
        $this->assertEquals('дніпровська', $method->invoke($resolver, 'Дніпровська територіальна громада'));
    }

    public function testIsSimilar(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('isSimilar');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($resolver, 'київ', 'київ', 100));
        $this->assertTrue($method->invoke($resolver, 'київ', 'киев', 75));
        $this->assertFalse($method->invoke($resolver, 'київ', 'харків', 85));
    }

    public function testGetLocationsReturnsAllLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $locations = $resolver->getLocations();

        $this->assertIsArray($locations);
        $this->assertNotEmpty($locations);

        $this->assertArrayHasKey(4, $locations);
        $this->assertArrayHasKey(434, $locations);
        $this->assertArrayHasKey(461, $locations);

        $this->assertEquals('Вінницька область', $locations[4]['name'] ?? null);
        $this->assertEquals('hromada', $locations[434]['type'] ?? null);
    }

    public function testFindBestMatchInListFuzzyMatchOnly(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('findBestMatchInList');
        $method->setAccessible(true);

        $locations = [
            1 => ['name' => 'Test Location Name', 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
            2 => ['name' => 'Another Place Name', 'type' => 'hromada', 'oblast_name' => 'Test Oblast'],
        ];

        $result = $method->invoke($resolver, 'Test Locatin Nam', $locations);

        $this->assertNotNull($result);
        $this->assertEquals('Test Location Name', $result['name']);
        $this->assertEquals('fuzzy', $result['matched_by']);
    }

    public function testCheckPrefixMatchReturnsNull(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('checkPrefixMatch');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'абвгд', 'xyzabc');

        $this->assertNull($result);
    }

    public function testCheckPrefixMatchLowScore(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('checkPrefixMatch');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, 'а', 'abcdefghij');

        $this->assertNull($result);
    }

    public function testMapToLocationReturnsMatchFromHromada(): void
    {
        $resolver = new NominatimGeoResolver(null, null);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('mapToLocation');
        $method->setAccessible(true);

        $nominatimData = [
            'address' => [
                'municipality' => 'Дніпровська міська громада',
                'state' => 'Дніпропетровська область'
            ]
        ];

        $result = $method->invoke($resolver, $nominatimData);

        $this->assertNotNull($result);
        $this->assertStringContainsString('Дніпровська', $result['name']);
    }
}
