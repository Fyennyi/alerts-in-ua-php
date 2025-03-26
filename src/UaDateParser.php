<?php

namespace AlertsUA;

use DateTime;
use DateTimeZone;

class UaDateParser
{
    public static function parseDate($date_string, $time_format = 'Y-m-d\TH:i:s.u\Z')
    {
        if ($date_string) {
            $kyiv_tz = new DateTimeZone('Europe/Kyiv');
            $utc_dt = DateTime::createFromFormat($time_format, $date_string, new DateTimeZone('UTC'));
            $utc_dt->setTimezone($kyiv_tz);

            return $utc_dt;
        }

        return null;
    }
}
