<?php

namespace Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\GeoLocationTrait;
use Fyennyi\AlertsInUa\Util\NominatimGeoResolver;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class DummyGeoLocationClient
{
    use GeoLocationTrait;

    public $httpClient;
    public $geoResolver;

    public function __construct()
    {
        $this->httpClient = new Client();
        $this->geoResolver = new NominatimGeoResolver();
    }
}

class GeoLocationTraitTest extends TestCase
{
    public function testTraitInstantiation(): void
    {
        $client = new DummyGeoLocationClient();
        $this->assertInstanceOf(DummyGeoLocationClient::class, $client);
    }
}
