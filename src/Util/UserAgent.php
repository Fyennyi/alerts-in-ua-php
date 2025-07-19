<?php

namespace Fyennyi\AlertsInUa\Util;

class UserAgent
{
    private const DEFAULT_AGENT = 'alerts-in-ua-php/0.2.7 (+https://github.com/Fyennyi/alerts-in-ua-php)';

    /**
     * Get User-Agent string for API requests
     *
     * @return string User-Agent string from environment variable or default value
     */
    public static function getUserAgent() : string
    {
        return getenv('AIU_USER_AGENT') ?: self::DEFAULT_AGENT;
    }
}
