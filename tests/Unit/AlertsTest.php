<?php

namespace Tests\Unit;

use ArrayIterator;
use DateTime;
use Fyennyi\AlertsInUa\Model\Alert;
use Fyennyi\AlertsInUa\Model\Alerts;
use PHPUnit\Framework\TestCase;

class AlertsTest extends TestCase
{
    private array $alertsData;

    private Alerts $alerts;

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
                    'location_uid' => 1,
                ],
                [
                    'id' => 2,
                    'location_title' => 'Харків',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T10:20:30.000Z',
                    'alert_type' => 'air_raid',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                    'location_uid' => 2,
                ],
                [
                    'id' => 3,
                    'location_title' => 'Харківська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-02T10:20:30.000Z',
                    'alert_type' => 'air_raid',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                    'location_uid' => 3,
                ],
                [
                    'id' => 4,
                    'location_title' => 'Полтавська область',
                    'location_type' => 'oblast',
                    'started_at' => '2023-01-02T11:00:00.000Z',
                    'alert_type' => 'chemical',
                    'location_oblast' => 'Полтавська область',
                    'location_oblast_uid' => 19,
                    'location_uid' => 4,
                ],
                [
                    'id' => 5,
                    'location_title' => 'Ізюмський район',
                    'location_type' => 'raion',
                    'started_at' => '2023-01-02T11:05:00.000Z',
                    'alert_type' => 'artillery_shelling',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                    'location_uid' => 99,
                ],
                [
                    'id' => 6,
                    'location_title' => 'Балаклійська громада',
                    'location_type' => 'hromada',
                    'started_at' => '2023-01-02T11:10:00.000Z',
                    'alert_type' => 'urban_fights',
                    'location_oblast' => 'Харківська область',
                    'location_oblast_uid' => 22,
                    'location_uid' => 100,
                ],
                [
                    'id' => 7,
                    'location_title' => 'Енергодар',
                    'location_type' => 'city',
                    'started_at' => '2023-01-02T11:15:00.000Z',
                    'alert_type' => 'nuclear',
                    'location_oblast' => 'Запорізька область',
                    'location_oblast_uid' => 8,
                    'location_uid' => 101,
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
        $this->assertCount(7, $this->alerts->getAllAlerts());
    }

    public function testConstructorWithEmptyAndInvalidData()
    {
        $emptyAlerts = new Alerts([]);
        $this->assertCount(0, $emptyAlerts->getAllAlerts());
        $this->assertNull($emptyAlerts->getLastUpdatedAt());
        $this->assertEquals('', $emptyAlerts->getDisclaimer());

        $invalidData = new Alerts([
            'alerts' => ['not an array', false, null],
            'meta' => 'not an array',
            'disclaimer' => 123
        ]);
        $this->assertCount(0, $invalidData->getAllAlerts());
        $this->assertNull($invalidData->getLastUpdatedAt());
        $this->assertEquals('', $invalidData->getDisclaimer());

        // Test when 'alerts' key is not an array
        $invalidAlertsField = new Alerts(['alerts' => 'this is not an array']);
        $this->assertCount(0, $invalidAlertsField->getAllAlerts());
    }

    public function testFilteringByAlertType()
    {
        $this->assertCount(3, $this->alerts->getAirRaidAlerts());
        $this->assertCount(1, $this->alerts->getArtilleryShellingAlerts());
        $this->assertCount(1, $this->alerts->getUrbanFightsAlerts());
        $this->assertCount(1, $this->alerts->getNuclearAlerts());

        $chemicalAlerts = $this->alerts->getChemicalAlerts();
        $this->assertCount(1, $chemicalAlerts);
        $this->assertEquals('chemical', $chemicalAlerts[0]->getAlertType());
    }

    public function testFilteringByLocationType()
    {
        $this->assertCount(2, $this->alerts->getOblastAlerts());
        $this->assertCount(1, $this->alerts->getRaionAlerts());
        $this->assertCount(1, $this->alerts->getHromadaAlerts());
        $this->assertCount(3, $this->alerts->getCityAlerts());
    }

    public function testFilteringByOblast()
    {
        $kharkivAlerts = $this->alerts->getAlertsByOblast('Харківська область');
        $this->assertCount(4, $kharkivAlerts);

        $kyivAlerts = $this->alerts->getAlertsByOblast('м. Київ');
        $this->assertCount(1, $kyivAlerts);
    }

    public function testFilteringByUid()
    {
        $this->assertCount(1, $this->alerts->getAlertsByLocationUid('101'));
        $this->assertCount(4, $this->alerts->getAlertsByOblastUid('22'));
    }

    public function testFilteringByLocationTitle()
    {
        $kharkivCityAlerts = $this->alerts->getAlertsByLocationTitle('Харків');
        $this->assertCount(1, $kharkivCityAlerts);
        $this->assertEquals('Харків', $kharkivCityAlerts[0]->getLocationTitle());
    }

    public function testFilteringByMultipleParameters()
    {
        $alerts = $this->alerts;
        $filteredAlerts = $alerts->filter('alert_type', 'air_raid', 'location_type', 'city');
        $this->assertCount(2, $filteredAlerts);
    }

    public function testFilterWithInvalidArgs()
    {
        // Odd number of args, the last one should be ignored
        $filtered = $this->alerts->filter('alert_type', 'air_raid', 'location_type');
        $this->assertCount(3, $filtered);

        // Invalid field type, should be ignored
        $filtered = $this->alerts->filter(123, 'air_raid');
        $this->assertCount(7, $filtered);
    }

    public function testInterfacesImplementation()
    {
        // Countable
        $this->assertCount(7, $this->alerts);
        $this->assertEquals(7, count($this->alerts));

        // IteratorAggregate
        $this->assertInstanceOf(ArrayIterator::class, $this->alerts->getIterator());
        $count = 0;
        foreach ($this->alerts as $alert) {
            $this->assertInstanceOf(Alert::class, $alert);
            $count++;
        }
        $this->assertEquals(7, $count);

        // JsonSerializable
        $json = json_encode($this->alerts);
        $this->assertIsString($json);
        $data = json_decode($json, true);
        $this->assertCount(7, $data['alerts']);
        $this->assertArrayHasKey('meta', $data);
        $this->assertEquals(7, $data['meta']['count']);
        $this->assertEquals('Test disclaimer', $data['disclaimer']);
    }

    public function testToString()
    {
        $string = (string)$this->alerts;
        $this->assertJson($string);
        $this->assertStringContainsString('Харківська область', $string);
        $this->assertStringContainsString('Test disclaimer', $string);
    }

    public function testToStringReturnsEmptyStringOnJsonFailure(): void
    {
        // Create a mock Alert with invalid UTF-8 characters in a string property
        $invalidUtf8Data = [
            'alerts' => [
                ['location_title' => "\xB1\x31"] // Invalid UTF-8 sequence
            ]
        ];
        $alerts = new Alerts($invalidUtf8Data);

        // Temporarily suppress error log output for this test
        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            // The __toString method should catch the JsonException and return an empty string
            $this->assertEquals('', (string)$alerts);
        } finally {
            // Restore the original error log setting
            ini_set('error_log', $originalErrorLog);
        }
    }
}
