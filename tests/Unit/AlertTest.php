<?php

namespace Tests\Unit;

use DateInterval;
use DateTime;
use Fyennyi\AlertsInUa\Model\Alert;
use Fyennyi\AlertsInUa\Model\Enum\AlertType;
use Fyennyi\AlertsInUa\Model\Enum\LocationType;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AlertTest extends TestCase
{
    private array $activeAlertData;

    private array $finishedAlertData;

    protected function setUp() : void
    {
        parent::setUp();

        $this->activeAlertData = [
            'id' => 1,
            'location_title' => 'м. Київ',
            'location_type' => 'city',
            'started_at' => '2022-03-15T14:09:26+02:00',
            'finished_at' => null,
            'updated_at' => '2022-03-15T14:09:26+02:00',
            'alert_type' => 'air_raid',
            'location_uid' => 31,
            'location_oblast' => 'м. Київ',
            'location_oblast_uid' => 31,
            'location_raion' => 'Київський район',
            'notes' => 'Active alert notes',
            'calculated' => false,
        ];

        $this->finishedAlertData = [
            'id' => 2,
            'location_title' => 'Харківська область',
            'location_type' => 'oblast',
            'started_at' => '2022-03-15T12:13:04+02:00',
            'finished_at' => '2022-03-15T13:53:16+02:00',
            'updated_at' => '2022-03-15T13:53:16+02:00',
            'alert_type' => 'artillery_shelling',
            'location_uid' => 32,
            'location_oblast' => 'Харківська область',
            'location_oblast_uid' => 32,
            'location_raion' => null,
            'notes' => 'Finished alert notes',
            'calculated' => true,
        ];
    }

    public function testAlertConstruction()
    {
        $data = [
            'id' => 123,
            'location_title' => 'Київ',
            'location_type' => 'city',
            'started_at' => '2023-01-02T10:15:30.000Z',
            'finished_at' => '2023-01-02T11:30:00.000Z',
            'updated_at' => '2023-01-02T11:30:00.000Z',
            'alert_type' => 'air_raid',
            'location_uid' => 31,
            'location_oblast' => 'м. Київ',
            'location_oblast_uid' => 31,
            'location_raion' => null,
            'notes' => 'Test alert',
            'calculated' => false
        ];

        $alert = new Alert($data);

        $this->assertEquals(123, $alert->getId());
        $this->assertEquals('Київ', $alert->getLocationTitle());
        $this->assertEquals(LocationType::CITY, $alert->getLocationType());
        $this->assertEquals(AlertType::AIR_RAID, $alert->getAlertType());
        $this->assertEquals(31, $alert->getLocationUid());
        $this->assertEquals('м. Київ', $alert->getLocationOblast());
        $this->assertEquals(31, $alert->getLocationOblastUid());
        $this->assertEquals('Test alert', $alert->getNotes());
        $this->assertFalse($alert->isCalculated());
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getStartedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getFinishedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getUpdatedAt());
        $this->assertTrue($alert->isFinished());
    }

    public function testAlertWithNulls()
    {
        $data = [
            'id' => 123,
            'location_title' => 'Київ',
            'finished_at' => null
        ];

        $alert = new Alert($data);

        $this->assertEquals(123, $alert->getId());
        $this->assertEquals('Київ', $alert->getLocationTitle());
        $this->assertNull($alert->getFinishedAt());
        $this->assertFalse($alert->isFinished());
        $this->assertTrue($alert->isActive());
    }

    public function testIsActive()
    {
        $activeAlert = new Alert($this->activeAlertData);
        $finishedAlert = new Alert($this->finishedAlertData);

        $this->assertTrue($activeAlert->isActive());
        $this->assertFalse($finishedAlert->isActive());
    }

    public function testGetDuration()
    {
        $activeAlert = new Alert($this->activeAlertData);
        $finishedAlert = new Alert($this->finishedAlertData);
        $noStartDateAlert = new Alert(['id' => 3]);

        $this->assertInstanceOf(DateInterval::class, $activeAlert->getDuration());
        $this->assertInstanceOf(DateInterval::class, $finishedAlert->getDuration());
        $this->assertEquals(6012, $finishedAlert->getDurationInSeconds());
        $this->assertNull($noStartDateAlert->getDuration());
        $this->assertNull($noStartDateAlert->getDurationInSeconds());
    }

    public function testGetDurationInSeconds()
    {
        $finishedAlert = new Alert($this->finishedAlertData);
        $this->assertEquals(6012, $finishedAlert->getDurationInSeconds());

        $activeAlert = new Alert($this->activeAlertData);
        $duration = $activeAlert->getDurationInSeconds();
        $this->assertIsInt($duration);
        $this->assertGreaterThan(0, $duration);
    }

    public function testIsType()
    {
        $alert = new Alert($this->activeAlertData);
        $this->assertTrue($alert->isType('air_raid'));
        $this->assertFalse($alert->isType('artillery_shelling'));
        
        // Test with Enum object
        $this->assertTrue($alert->isType(AlertType::AIR_RAID));
        $this->assertFalse($alert->isType(AlertType::ARTILLERY_SHELLING));
    }

    public function testIsInLocation()
    {
        $alert = new Alert($this->activeAlertData);
        $this->assertTrue($alert->isInLocation('Київ'));
        $this->assertTrue($alert->isInLocation('Київський'));
        $this->assertFalse($alert->isInLocation('Львів'));
    }

    public function testGetProperty()
    {
        $alert = new Alert($this->finishedAlertData);
        $this->assertEquals(2, $alert->getProperty('id'));
        $this->assertEquals('Харківська область', $alert->getProperty('location_title'));
        $this->assertEquals(LocationType::OBLAST, $alert->getProperty('location_type'));
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getProperty('started_at'));
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getProperty('finished_at'));
        $this->assertInstanceOf(\DateTimeInterface::class, $alert->getProperty('updated_at'));
        $this->assertEquals(AlertType::ARTILLERY_SHELLING, $alert->getProperty('alert_type'));
        $this->assertEquals(32, $alert->getProperty('location_uid'));
        $this->assertEquals('Харківська область', $alert->getProperty('location_oblast'));
        $this->assertEquals(32, $alert->getProperty('location_oblast_uid'));
        $this->assertNull($alert->getProperty('location_raion'));
        $this->assertEquals('Finished alert notes', $alert->getProperty('notes'));
        $this->assertTrue($alert->getProperty('calculated'));
        $this->assertNull($alert->getProperty('non_existent_property'));
    }

    public function testToArray()
    {
        $alert = new Alert($this->activeAlertData);
        $array = $alert->toArray();

        $this->assertIsArray($array);
        $this->assertEquals($this->activeAlertData['id'], $array['id']);
        $this->assertEquals($this->activeAlertData['location_title'], $array['location_title']);
        $this->assertTrue($array['is_active']);
        $this->assertIsInt($array['duration']);
    }

    public function testToJson()
    {
        $alert = new Alert($this->activeAlertData);
        $json = $alert->toJson();
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertIsArray($decoded);
        $this->assertEquals($this->activeAlertData['id'], $decoded['id']);
    }

    public function testToJsonThrowsExceptionOnFailure()
    {
        // Creating a mock that will fail on json_encode
        $alertData = ['notes' => "\xB1\x31"]; // Invalid UTF-8 sequence
        $alert = new Alert($alertData);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode alert to JSON: Malformed UTF-8 characters, possibly incorrectly encoded');

        $alert->toJson();
    }

    public function testConstructorWithMissingKeys()
    {
        $alert = new Alert([]);
        $this->assertSame(0, $alert->getId());
        $this->assertSame('', $alert->getLocationTitle());
        $this->assertSame(LocationType::UNKNOWN, $alert->getLocationType());
        $this->assertNull($alert->getStartedAt());
        $this->assertNull($alert->getFinishedAt());
        $this->assertNull($alert->getUpdatedAt());
        $this->assertSame(AlertType::UNKNOWN, $alert->getAlertType());
        $this->assertNull($alert->getLocationUid());
        $this->assertNull($alert->getLocationOblast());
        $this->assertNull($alert->getLocationOblastUid());
        $this->assertNull($alert->getLocationRaion());
        $this->assertNull($alert->getNotes());
        $this->assertFalse($alert->isCalculated());
    }

    public function testConstructorWithUidAsString()
    {
        $data = [
            'location_uid' => '31',
            'location_oblast_uid' => '32'
        ];
        $alert = new Alert($data);
        $this->assertSame(31, $alert->getLocationUid());
        $this->assertSame(32, $alert->getLocationOblastUid());
    }

    public function testToStringReturnsEmptyStringOnJsonEncodeFailure()
    {
        $alert = new Alert($this->activeAlertData);
        
        $reflection = new \ReflectionClass($alert);
        $property = $reflection->getProperty('location_title');
        $property->setAccessible(true);
        // Insert invalid UTF-8 to cause json_encode error
        $property->setValue($alert, "\xB1\x31");

        $originalErrorLog = ini_get('error_log');
        ini_set('error_log', '/dev/null');

        try {
            $this->assertEquals('', (string)$alert);
        } finally {
            ini_set('error_log', $originalErrorLog);
        }
    }
}
