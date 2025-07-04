<?php

namespace Tests\Unit;

use DateTime;
use Fyennyi\AlertsInUa\Util\UaDateParser;
use PHPUnit\Framework\TestCase;

class UaDateParserTest extends TestCase
{
    public function testParseValidISO8601Date()
    {
        $dateString = '2023-01-02T10:15:30.000Z';
        $dateTime = UaDateParser::parseDate($dateString);

        $this->assertInstanceOf(DateTime::class, $dateTime);
        $this->assertEquals('Europe/Kyiv', $dateTime->getTimezone()->getName());

        // Since Kyiv is UTC+2/UTC+3, the parsed time will be later than UTC
        $this->assertGreaterThanOrEqual('10:15:30', $dateTime->format('H:i:s'));
    }

    public function testParseNullDate()
    {
        $this->assertNull(UaDateParser::parseDate(null));
        $this->assertNull(UaDateParser::parseDate(''));
    }

    public function testParseVariousDateFormats()
    {
        $formats = [
            '2023/01/02 10:15:30',
            '2023-01-02 10:15:30',
            '2023-01-02T10:15:30',
            '2023-01-02T10:15:30.000',
        ];

        foreach ($formats as $format) {
            $dateTime = UaDateParser::parseDate($format);
            $this->assertInstanceOf(DateTime::class, $dateTime);
        }
    }
}
