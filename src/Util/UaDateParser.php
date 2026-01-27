<?php

/*
 *
 *     _    _           _       ___       _   _
 *    / \  | | ___ _ __| |_ ___|_ _|_ __ | | | | __ _
 *   / _ \ | |/ _ \ '__| __/ __|| || '_ \| | | |/ _` |
 *  / ___ \| |  __/ |  | |_\__ \| || | | | |_| | (_| |
 * /_/   \_\_|\___|_|   \__|___/___|_| |_|\___/ \__,_|
 *
 * This program is free software: you can redistribute and/or modify
 * it under the terms of the CSSM Unlimited License v2.0.
 *
 * This license permits unlimited use, modification, and distribution
 * for any purpose while maintaining authorship attribution.
 *
 * The software is provided "as is" without warranty of any kind.
 *
 * @author Serhii Cherneha
 * @link https://chernega.eu.org/
 *
 *
 */

namespace Fyennyi\AlertsInUa\Util;

use DateTime;
use DateTimeZone;

class UaDateParser
{
    /**
     * Parse date string to DateTime object with Kyiv timezone
     *
     * @param  string|null  $date_string  Date string to parse
     * @param  string       $time_format  Expected format of the date string
     * @return DateTime|null              DateTime object in Kyiv timezone or null if parsing fails
     */
    public static function parseDate(?string $date_string, string $time_format = 'Y-m-d\TH:i:s.u\Z') : ?DateTime
    {
        if (empty($date_string)) {
            return null;
        }

        $kyiv_tz = new DateTimeZone('Europe/Kyiv');
        $utc_dt = DateTime::createFromFormat($time_format, $date_string, new DateTimeZone('UTC'));

        if (false === $utc_dt) {
            /** @var list<string> */
            $formats = [
                'Y/m/d H:i:s',
                'Y/m/d H:i:s e',
                'Y/m/d H:i:s O',
                'Y-m-d H:i:s',
                'Y-m-d H:i:s e',
                'Y-m-d H:i:s O',
                'Y-m-d\TH:i:s',
                'Y-m-d\TH:i:s.u',
                'Y-m-d\TH:i:sP',
            ];

            foreach ($formats as $format) {
                $utc_dt = DateTime::createFromFormat($format, $date_string);
                if (false !== $utc_dt) {
                    break;
                }
            }

            if (false === $utc_dt) {
                $timestamp = strtotime($date_string);
                if (false !== $timestamp) {
                    $utc_dt = new DateTime();
                    $utc_dt->setTimestamp($timestamp);
                } else {
                    return null;
                }
            }
        }

        $utc_dt->setTimezone($kyiv_tz);

        return $utc_dt;
    }
}
