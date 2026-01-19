<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Util\TransliterationHelper;
use PHPUnit\Framework\TestCase;

class TransliterationHelperTest extends TestCase
{
    public function testUkrainianToLatin(): void
    {
        $this->assertEquals('Kyiv', TransliterationHelper::ukrainianToLatin('Київ'));
        $this->assertEquals('Lviv', TransliterationHelper::ukrainianToLatin('Львів'));
        $this->assertEquals('Odesa', TransliterationHelper::ukrainianToLatin('Одеса'));
    }

    public function testNormalizeForMatching(): void
    {
        $this->assertEquals('kyiv', TransliterationHelper::normalizeForMatching('Київ'));
        $this->assertEquals('lvivska', TransliterationHelper::normalizeForMatching('Львівська область'));
        $this->assertEquals('kyiv', TransliterationHelper::normalizeForMatching("Київ м."));
    }

    public function testNormalizeEnglish(): void
    {
        $this->assertEquals('kyiv', TransliterationHelper::normalizeEnglish('Kyiv'));
        $this->assertEquals('lviv', TransliterationHelper::normalizeEnglish('Lviv oblast'));
        $this->assertEquals('kyiv', TransliterationHelper::normalizeEnglish("Kyiv city"));
    }
}