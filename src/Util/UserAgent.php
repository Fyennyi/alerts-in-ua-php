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

class UserAgent
{
    private const DEFAULT_AGENT = 'alerts-in-ua-php/0.2.9 (+https://github.com/Fyennyi/alerts-in-ua-php)';

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
