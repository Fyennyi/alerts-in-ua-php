<?php

namespace AlertsUA;

class UserAgent
{
    private const DEFAULT_AGENT = 'aiu-php-client/1.0 (+https://alerts.in.ua)';

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
