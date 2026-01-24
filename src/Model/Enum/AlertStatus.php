<?php

namespace Fyennyi\AlertsInUa\Model\Enum;

use JsonSerializable;

/**
 * Enumeration of alert statuses
 */
enum AlertStatus: string implements JsonSerializable
{
    case ACTIVE = 'active';
    case PARTLY = 'partly';
    case NO_ALERT = 'no_alert';

    /**
     * Create from string with fallback to NO_ALERT
     */
    public static function fromString(?string $value) : self
    {
        if ($value === null) {
            return self::NO_ALERT;
        }

        return self::tryFrom($value) ?? self::NO_ALERT;
    }

    public function jsonSerialize() : string
    {
        return $this->value;
    }
}
