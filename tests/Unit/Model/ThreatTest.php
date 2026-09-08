<?php

namespace Tests\Unit\Model;

use Fyennyi\AlertsInUa\Model\Enum\AlertLevel;
use Fyennyi\AlertsInUa\Model\Enum\ThreatType;
use Fyennyi\AlertsInUa\Model\Threat;
use PHPUnit\Framework\TestCase;

class ThreatTest extends TestCase
{
    public function testConstructorAndGetters()
    {
        $data = [
            'threat_type' => 'drones',
            'level' => 'yellow',
            'started_at' => '2022-04-04T16:45:39.681Z',
            'source_message' => 'Дронова загроза (жовтий рівень)'
        ];

        $threat = new Threat($data);

        $this->assertEquals(ThreatType::DRONES, $threat->getThreatType());
        $this->assertEquals(AlertLevel::YELLOW, $threat->getLevel());
        $this->assertInstanceOf(\DateTimeInterface::class, $threat->getStartedAt());
        $this->assertEquals('Дронова загроза (жовтий рівень)', $threat->getSourceMessage());
    }

    public function testConstructorWithMissingKeys()
    {
        $threat = new Threat([]);

        $this->assertEquals(ThreatType::UNKNOWN, $threat->getThreatType());
        $this->assertEquals(AlertLevel::UNKNOWN, $threat->getLevel());
        $this->assertNull($threat->getStartedAt());
        $this->assertNull($threat->getSourceMessage());
    }

    public function testToArrayAndJsonSerialize()
    {
        $data = [
            'threat_type' => 'cruise_missiles',
            'level' => 'red',
            'started_at' => '2022-04-04T16:45:39.681Z',
            'source_message' => 'Ракетна небезпека'
        ];

        $threat = new Threat($data);
        $array = $threat->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('cruise_missiles', $array['threat_type']);
        $this->assertEquals('red', $array['level']);
        $this->assertArrayHasKey('started_at', $array);
        $this->assertNotNull($array['started_at']);
        $this->assertEquals('Ракетна небезпека', $array['source_message']);

        // jsonSerialize uses toArray()
        $this->assertEquals($array, $threat->jsonSerialize());
    }

    public function testToJson()
    {
        $data = [
            'threat_type' => 'drones',
            'level' => 'yellow',
            'started_at' => '2022-04-04T16:45:39.000Z',
            'source_message' => 'Тест'
        ];

        $threat = new Threat($data);
        $json = $threat->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('drones', $decoded['threat_type']);
        $this->assertEquals('yellow', $decoded['level']);
    }

    public function testToJsonThrowsExceptionOnFailure()
    {
        // Pass an invalid UTF-8 sequence to trigger a json_encode error
        $data = [
            'source_message' => "\xB1\x31"
        ];

        $threat = new Threat($data);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode threat to JSON: Malformed UTF-8 characters, possibly incorrectly encoded');

        $threat->toJson();
    }
}
