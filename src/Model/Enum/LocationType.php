<?php

namespace Fyennyi\AlertsInUa\Model\Enum;

use JsonSerializable;

/**
 * Enumeration of location types in Ukraine
 */
enum LocationType: string implements JsonSerializable
{
    case OBLAST = 'oblast';
    case RAION = 'raion';
    case CITY = 'city';
    case HROMADA = 'hromada';
    case UNKNOWN = 'unknown';

    /**
     * Create from string with fallback to UNKNOWN
     */
    public static function fromString(?string $value) : self
    {
        if ($value === null) {
            return self::UNKNOWN;
        }

        return self::tryFrom($value) ?? self::UNKNOWN;
    }

    public function jsonSerialize() : string
    {
        return $this->value;
    }

    public function __toString() : string
    {
        return $this->value;
    }
}
