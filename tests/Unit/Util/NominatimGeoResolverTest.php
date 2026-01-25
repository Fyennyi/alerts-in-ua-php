<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use Fyennyi\Nominatim\Client as NominatimClient;
use Fyennyi\Nominatim\Model\Place;
use GuzzleHttp\Promise\Create;
use Psr\SimpleCache\CacheInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class NominatimGeoResolverTest extends TestCase
{
    private string $tempLocationsPath;

    protected function setUp() : void
    {
        $this->tempLocationsPath = sys_get_temp_dir() . '/test_locations.json';
        $testData = [
            "188" => [
                "name" => "Джулинська територіальна громада",
                "type" => "hromada",
                "oblast_id" => 4,
                "osm_id" => 12343496
            ],
            "114" => [
                "name" => "Сумський район",
                "type" => "district",
                "oblast_id" => 20,
                "osm_id" => 11923012
            ],
            "4" => [
                "name" => "Вінницька область",
                "type" => "oblast",
                "oblast_id" => 4,
                "osm_id" => 90726
            ]
        ];
        file_put_contents($this->tempLocationsPath, json_encode($testData));
    }

    protected function tearDown() : void
    {
        if (file_exists($this->tempLocationsPath)) {
            unlink($this->tempLocationsPath);
        }
    }

    public function testConstructorLoadsLocations() : void
    {
        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath);
        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);
        $this->assertCount(3, $resolver->getLocations());
    }

    public function testFindByCoordinatesAsyncSuccess() : void
    {
        $place = $this->createMock(Place::class);
        $place->method('getOsmId')->willReturn(12343496);

        $nominatim = $this->createMock(NominatimClient::class);
        $nominatim->expects($this->once())
            ->method('reverse')
            ->willReturn(Create::promiseFor($place));

        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath, $nominatim);
        $result = $resolver->findByCoordinatesAsync(48.5325, 29.9233)->wait();

        $this->assertNotNull($result);
        $this->assertEquals(188, $result['uid']);
        $this->assertEquals('osm_id_zoom_10', $result['matched_by']);
    }

    public function testFindByCoordinatesAsyncFallback() : void
    {
        $placeUnknown = $this->createMock(Place::class);
        $placeUnknown->method('getOsmId')->willReturn(999);

        $placeKnown = $this->createMock(Place::class);
        $placeKnown->method('getOsmId')->willReturn(11923012);

        $nominatim = $this->createMock(NominatimClient::class);
        $nominatim->expects($this->exactly(2))
            ->method('reverse')
            ->willReturnOnConsecutiveCalls(
                Create::promiseFor($placeUnknown),
                Create::promiseFor($placeKnown)
            );

        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath, $nominatim);
        $result = $resolver->findByCoordinatesAsync(50.0, 30.0)->wait();

        $this->assertNotNull($result);
        $this->assertEquals(114, $result['uid']);
        $this->assertEquals('osm_id_zoom_8', $result['matched_by']);
    }

    public function testFindByCoordinatesAsyncNoMatch() : void
    {
        $nominatim = $this->createMock(NominatimClient::class);
        $nominatim->method('reverse')->willReturn(Create::promiseFor(null));

        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath, $nominatim);
        $result = $resolver->findByCoordinatesAsync(0.0, 0.0)->wait();

        $this->assertNull($result);
    }

    public function testFindByCoordinatesSyncWrapper() : void
    {
        $place = $this->createMock(Place::class);
        $place->method('getOsmId')->willReturn(12343496);

        $nominatim = $this->createMock(NominatimClient::class);
        $nominatim->method('reverse')->willReturn(Create::promiseFor($place));

        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath, $nominatim);

        // Call the synchronous method which calls wait() internally
        $result = $resolver->findByCoordinates(48.5325, 29.9233);

        $this->assertNotNull($result);
        $this->assertEquals(188, $result['uid']);
    }

    public function testConstructorWithNonExistentLocationsPath() : void
    {
        $nonExistentPath = '/non/existent/path/locations.json';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to read locations.json');
        new NominatimGeoResolver(null, $nonExistentPath);
    }

    public function testConstructorWithInvalidLocationsPath() : void
    {
        $invalidLocationsPath = sys_get_temp_dir() . '/invalid_locations.json';
        file_put_contents($invalidLocationsPath, 'invalid json');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid locations.json structure');
        try {
            new NominatimGeoResolver(null, $invalidLocationsPath);
        } finally {
            unlink($invalidLocationsPath);
        }
    }

    public function testMatchByOsmIdReturnsNullOnMissingId() : void
    {
        $place = $this->createMock(Place::class);
        $place->method('getOsmId')->willReturn(null);

        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath);
        $reflection = new \ReflectionClass($resolver);
        $method = $reflection->getMethod('matchByOsmId');
        $method->setAccessible(true);

        $result = $method->invoke($resolver, $place, 10);
        $this->assertNull($result);
    }

    public function testGetLocationsReturnsAllLocations() : void
    {
        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath);
        $locations = $resolver->getLocations();

        $this->assertIsArray($locations);
        $this->assertArrayHasKey(188, $locations);
        $this->assertEquals(12343496, $locations[188]['osm_id']);
    }
}