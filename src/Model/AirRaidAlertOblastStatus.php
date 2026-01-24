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

namespace Fyennyi\AlertsInUa\Model;

use Fyennyi\AlertsInUa\Model\Enum\AlertStatus;
use JsonSerializable;

class AirRaidAlertOblastStatus implements JsonSerializable
{
    private string $oblast;

    private AlertStatus $status;

    private const STATUS_MAP = [
        'A' => AlertStatus::ACTIVE,
        'P' => AlertStatus::PARTLY,
        'N' => AlertStatus::NO_ALERT,
    ];

    /**
     * Constructor for AirRaidAlertOblastStatus
     *
     * @param  string  $oblast  Oblast name
     * @param  string  $status  Alert status code
     * @param  bool  $oblast_level_only  Whether to apply oblast-level filtering
     */
    public function __construct(string $oblast, string $status, bool $oblast_level_only = false)
    {
        $this->oblast = $oblast;
        $resolved_status = self::STATUS_MAP[$status] ?? AlertStatus::NO_ALERT;

        if ($resolved_status === AlertStatus::PARTLY && $oblast_level_only) {
            $resolved_status = AlertStatus::NO_ALERT;
        }

        $this->status = $resolved_status;
    }

    /**
     * Get oblast name
     *
     * @return string Oblast name
     */
    public function getOblast() : string
    {
        return $this->oblast;
    }

    /**
     * Get alert status
     *
     * @return AlertStatus Alert status enum
     */
    public function getStatus() : AlertStatus
    {
        return $this->status;
    }

    /**
     * Check if the oblast has an active alert
     *
     * @return bool True if active
     */
    public function isActive() : bool
    {
        return $this->status === AlertStatus::ACTIVE;
    }

    /**
     * Check if the oblast has a partly active alert
     *
     * @return bool True if partly active
     */
    public function isPartlyActive() : bool
    {
        return $this->status === AlertStatus::PARTLY;
    }

    /**
     * Check if the oblast has no alert
     *
     * @return bool True if no alert
     */
    public function isNoAlert() : bool
    {
        return $this->status === AlertStatus::NO_ALERT;
    }

    /**
     * Get string representation of the oblast status
     *
     * @return string JSON representation
     */
    public function __toString() : string
    {
        try {
            return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Failed to serialize AirRaidAlertOblastStatus to string: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize() : array
    {
        return [
            'oblast' => $this->oblast,
            'status' => $this->status->value,
        ];
    }
}