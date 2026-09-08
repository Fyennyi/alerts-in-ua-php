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

namespace Fyennyi\AlertsInUa\Model\Enum;

use JsonSerializable;

/**
 * Enumeration of alert levels in Ukraine
 */
enum AlertLevel : string implements JsonSerializable
{
    case RED = 'red';
    case YELLOW = 'yellow';
    case UNKNOWN = 'unknown';

    /**
     * Create from string with fallback to UNKNOWN
     *
     * @param  string|null $value Raw string value
     * @return self
     */
    public static function fromString(?string $value) : self
    {
        if (null === $value) {
            return self::UNKNOWN;
        }

        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    public function jsonSerialize() : string
    {
        return $this->value;
    }
}
