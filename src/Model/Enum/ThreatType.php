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
 * Enumeration of specific threat types in Ukraine
 */
enum ThreatType : string implements JsonSerializable
{
    case TACTIC_AIRCRAFT_ACTIVITY = 'tactic_aircraft_activity';
    case STRATEGIC_AIRCRAFT_ACTIVITY = 'strategic_aircraft_activity';
    case MIG31K_DEPARTURE = 'mig31k_departure';
    case BALLISTIC_MISSILES = 'ballistic_missiles';
    case CRUISE_MISSILES = 'cruise_missiles';
    case UNSPECIFIED_MISSILES = 'unspecified_missiles';
    case DRONES = 'drones';
    case GUIDED_AERIAL_BOMBS = 'guided_aerial_bombs';
    case AIR_DEFENSE = 'air_defense';
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
