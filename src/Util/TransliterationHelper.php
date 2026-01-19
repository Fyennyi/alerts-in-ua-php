<?php

namespace Fyennyi\AlertsInUa\Util;

use ashtokalo\translit\Translit;

class TransliterationHelper
{
    private static ?Translit $instance = null;

    private static function getInstance(): Translit
    {
        if (self::$instance === null) {
            self::$instance = Translit::object();
        }

        return self::$instance;
    }

    public static function ukrainianToLatin(string $ukrainian): string
    {
        return self::getInstance()->convert($ukrainian, 'uk');
    }

    public static function normalizeForMatching(string $name): string
    {
        $transliterated = self::ukrainianToLatin($name);

        $normalized = strtolower($transliterated);
        $normalized = str_replace([' oblast', ' m.', 'm. ', "'", '-'], '', $normalized);
        $normalized = str_replace(['  ', '  '], ' ', $normalized);

        return trim($normalized);
    }

    public static function normalizeEnglish(string $name): string
    {
        $normalized = strtolower($name);
        $normalized = str_replace([' oblast', ' oblast.', ' city', "'"], '', $normalized);
        $normalized = str_replace(['  ', '  '], ' ', $normalized);

        return trim($normalized);
    }
}
