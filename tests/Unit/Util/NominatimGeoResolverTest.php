<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class NominatimGeoResolverTest extends TestCase
{
    private string $tempLocationsPath;

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        if (file_exists($this->tempLocationsPath)) {
            unlink($this->tempLocationsPath);
        }
    }

    public function testConstructorLoadsLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath);
        $this->assertInstanceOf(NominatimGeoResolver::class, $resolver);
        $this->assertCount(3, $resolver->getLocations());
    }

    public function testFindByCoordinatesWithCacheHit(): void
    {
        $cache = $this->createMock(SmartCacheManager::class);
        $cache->expects($this->once())
            ->method('getCachedData')
            ->willReturn(['uid' => 188, 'name' => 'Джулинська територіальна громада']);

        $resolver = new NominatimGeoResolver($cache, $this->tempLocationsPath);
        $result = $resolver->findByCoordinates(48.5325, 29.9233);

        $this->assertEquals(188, $result['uid']);
    }

    public function testMatchByOsmIdAtZoom10(): void
    {
        $resolver = $this->getMockBuilder(NominatimGeoResolver::class)
            ->setConstructorArgs([null, $this->tempLocationsPath])
            ->onlyMethods(['reverseGeocode'])
            ->getMock();

        // Should call zoom 10 and find match
        $resolver->expects($this->once())
            ->method('reverseGeocode')
            ->with(48.5325, 29.9233, 10)
            ->willReturn(['osm_id' => 12343496]);

        $result = $resolver->findByCoordinates(48.5325, 29.9233);

        $this->assertNotNull($result);
        $this->assertEquals(188, $result['uid']);
        $this->assertEquals('osm_id_zoom_10', $result['matched_by']);
    }

    public function testFallbackToZoom8(): void
    {
        $resolver = $this->getMockBuilder(NominatimGeoResolver::class)
            ->setConstructorArgs([null, $this->tempLocationsPath])
            ->onlyMethods(['reverseGeocode'])
            ->getMock();

        // Zoom 10 returns unknown ID
        $resolver->expects($this->exactly(2))
            ->method('reverseGeocode')
            ->willReturnMap([
                [50.0, 30.0, 10, ['osm_id' => 999999]], // Unknown
                [50.0, 30.0, 8, ['osm_id' => 11923012]], // Found Sumy district
            ]);

        $result = $resolver->findByCoordinates(50.0, 30.0);

        $this->assertNotNull($result);
        $this->assertEquals(114, $result['uid']);
        $this->assertEquals('osm_id_zoom_8', $result['matched_by']);
    }

    public function testFallbackToZoom5(): void
    {
        $resolver = $this->getMockBuilder(NominatimGeoResolver::class)
            ->setConstructorArgs([null, $this->tempLocationsPath])
            ->onlyMethods(['reverseGeocode'])
            ->getMock();

        $resolver->expects($this->exactly(3))
            ->method('reverseGeocode')
            ->willReturnMap([
                [50.0, 30.0, 10, ['osm_id' => 111]],
                [50.0, 30.0, 8, ['osm_id' => 222]],
                [50.0, 30.0, 5, ['osm_id' => 90726]], // Found Vinnytsia oblast
            ]);

        $result = $resolver->findByCoordinates(50.0, 30.0);

        $this->assertNotNull($result);
        $this->assertEquals(4, $result['uid']);
        $this->assertEquals('osm_id_zoom_5', $result['matched_by']);
    }

    public function testReturnsNullIfNoMatchFound(): void
    {
        $resolver = $this->getMockBuilder(NominatimGeoResolver::class)
            ->setConstructorArgs([null, $this->tempLocationsPath])
            ->onlyMethods(['reverseGeocode'])
            ->getMock();

        $resolver->expects($this->exactly(3))
            ->method('reverseGeocode')
            ->willReturn(['osm_id' => 999999]);

        $result = $resolver->findByCoordinates(0.0, 0.0);

        $this->assertNull($result);
    }

    public function testGetLocationsReturnsAllLocations(): void
    {
        $resolver = new NominatimGeoResolver(null, $this->tempLocationsPath);
        $locations = $resolver->getLocations();

        $this->assertIsArray($locations);
        $this->assertArrayHasKey(188, $locations);
        $this->assertEquals(12343496, $locations[188]['osm_id']);
    }
}