<?php

namespace Fyennyi\AlertsInUa\Model\Enum;

use JsonSerializable;

/**
 * Enumeration of alert types in Ukraine
 */
enum AlertType: string implements JsonSerializable
{
    case AIR_RAID = 'air_raid';
    case ARTILLERY_SHELLING = 'artillery_shelling';
    case URBAN_FIGHTS = 'urban_fights';
    case CHEMICAL = 'chemical';
    case NUCLEAR = 'nuclear';
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
