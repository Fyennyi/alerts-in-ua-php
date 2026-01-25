<?php

namespace Tests\Unit\Util;

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
            $this->assertInstanceOf(DateTime::class, $dateTime, "Failed to parse format: {$format}");
        }
    }

    public function testParseDateWithTimezone()
    {
        $dateString = '2023-07-20 12:00:00+03:00';
        $dateTime = UaDateParser::parseDate($dateString);
        $this->assertInstanceOf(DateTime::class, $dateTime);
        $this->assertEquals('12:00:00', $dateTime->format('H:i:s'));
        $this->assertEquals('Europe/Kyiv', $dateTime->getTimezone()->getName());
    }

    public function testParseDateWithStrtotimeFallback()
    {
        // This format is not in the list, so it should fall back to strtotime
        $dateString = '20 July 2025 10:00:00 UTC';
        $dateTime = UaDateParser::parseDate($dateString);
        $this->assertInstanceOf(DateTime::class, $dateTime);
        $this->assertEquals('13:00:00', $dateTime->format('H:i:s')); // Kyiv is UTC+3 in July
    }

    public function testParseInvalidDate()
    {
        $this->assertNull(UaDateParser::parseDate('not a date'));
        // strtotime('2023-02-30') results in a valid date (2023-03-02), so we check for warnings
        UaDateParser::parseDate('2023-02-30 10:00:00');
        $errors = DateTime::getLastErrors();
        $this->assertGreaterThan(0, $errors['warning_count']);
    }
}
