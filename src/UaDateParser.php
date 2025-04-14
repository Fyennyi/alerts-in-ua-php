<?php

namespace AlertsUA;

use DateTime;
use DateTimeZone;

class UaDateParser
{
    public static function parseDate($date_string, $time_format = 'Y-m-d\TH:i:s.u\Z')
    {
        if (empty($date_string)) {
            return null;
        }

        $kyiv_tz = new DateTimeZone('Europe/Kyiv');
        $utc_dt = DateTime::createFromFormat($time_format, $date_string, new DateTimeZone('UTC'));

        if (false === $utc_dt) {
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
                    $utc_dt = new DateTime;
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
