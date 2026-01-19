<?php

namespace Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\GeoLocationTrait;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class GeoLocationTraitTest extends TestCase
{
    public function testTraitMethods(): void
    {
        // Create a mock class that uses the trait
        $mock = new class {
            use GeoLocationTrait;

            public function __construct()
            {
                $this->httpClient = $this->createMock(Client::class);
                $this->geoResolver = $this->createMock(NominatimGeoResolver::class);
            }

            protected function createMock($class)
            {
                return \PHPUnit\Framework\TestCase::createMock($class);
            }
        };

        $this->assertInstanceOf(\stdClass::class, $mock);
    }
}