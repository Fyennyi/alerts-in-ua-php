<?php

namespace Tests\Unit;

use DateTime;
use Fyennyi\AlertsInUa\Model\Alerts;
use PHPUnit\Framework\TestCase;

class AlertsTest extends TestCase
{
    private $alertsData;

    private $alerts;

    protected function setUp() : void
    {
        $this->alertsData = [
            'alerts' => [
                [
                    'id' => 1,
                    'location_title' => 'Київ',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T10:15:30.000Z',
                    'alert_type' => 'air_raid',
                    'location_oblast' => 'м. Київ',
                    'location_oblast_uid' => 31,
                ],
                [
                    'id' => 2,
                    'location_title' => 'Харків',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T10:20:30.000Z',
                    'alert_type' => 'air_raid',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                ],
                [
                    'id' => 3,
                    'location_title' => 'Харківська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-02T10:20:30.000Z',
                    'alert_type' => 'air_raid',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                ],
                [
                    'id' => 4,
                    'location_title' => 'Полтавська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-02T11:00:00.000Z',
                    'alert_type' => 'chemical',
                    'location_oblast' => 'Полтавська область',
                    'location_oblast_uid' => 19,
                ]
            ],
            'meta' => [
                'last_updated_at' => '2023-01-02T11:30:00.000Z'
            ],
            'disclaimer' => 'Test disclaimer'
        ];

        $this->alerts = new Alerts($this->alertsData);
    }

    public function testAlertsConstruction()
    {
        $this->assertInstanceOf(DateTime::class, $this->alerts->getLastUpdatedAt());
        $this->assertEquals('Test disclaimer', $this->alerts->getDisclaimer());
        $this->assertCount(4, $this->alerts->getAllAlerts());
    }

    public function testFilteringByAlertType()
    {
        $airRaidAlerts = $this->alerts->getAirRaidAlerts();
        $this->assertCount(3, $airRaidAlerts);

        $chemicalAlerts = $this->alerts->getChemicalAlerts();
        $this->assertCount(1, $chemicalAlerts);
        $this->assertEquals('chemical', $chemicalAlerts[array_key_first($chemicalAlerts)]->alert_type);
    }

    public function testFilteringByLocationType()
    {
        $oblastAlerts = $this->alerts->getOblastAlerts();
        $this->assertCount(2, $oblastAlerts);
        $this->assertEquals('oblast', $oblastAlerts[array_key_first($oblastAlerts)]->location_type);

        $cityAlerts = $this->alerts->getCityAlerts();
        $this->assertCount(2, $cityAlerts);
        $this->assertEquals('city', $cityAlerts[array_key_first($cityAlerts)]->location_type);
    }

    public function testFilteringByOblast()
    {
        $kharkivAlerts = $this->alerts->getAlertsByOblast('Харківська область');
        $this->assertCount(2, $kharkivAlerts);

        $kyivAlerts = $this->alerts->getAlertsByOblast('м. Київ');
        $this->assertCount(1, $kyivAlerts);
    }

    public function testFilteringByLocationTitle()
    {
        $kharkivCityAlerts = $this->alerts->getAlertsByLocationTitle('Харків');
        $this->assertCount(1, $kharkivCityAlerts);
        $this->assertEquals('Харків', $kharkivCityAlerts[array_key_first($kharkivCityAlerts)]->location_title);
    }

    public function testFilteringByMultipleParameters()
    {
        $alerts = $this->alerts;
        $filteredAlerts = $alerts->filter('alert_type', 'air_raid', 'location_type', 'city');
        $this->assertCount(2, $filteredAlerts);
    }
}
