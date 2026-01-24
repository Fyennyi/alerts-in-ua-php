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

use JsonSerializable;

class AirRaidAlertOblastStatus implements JsonSerializable
{
    private string $oblast;

    private string $status;

    private const STATUS_MAP = [
        'A' => 'active',
        'P' => 'partly',
        'N' => 'no_alert',
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
        $resolved_status = self::STATUS_MAP[$status] ?? 'no_alert';

        if ($resolved_status === 'partly' && $oblast_level_only) {
            $resolved_status = 'no_alert';
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
     * @return string Alert status code
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Check if the oblast has an active alert
     *
     * @return bool
     */
    public function isActive() : bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the oblast has a partly active alert
     *
     * @return bool
     */
    public function isPartlyActive() : bool
    {
        return $this->status === 'partly';
    }

    /**
     * Check if the oblast has no alert
     *
     * @return bool
     */
    public function isNoAlert() : bool
    {
        return $this->status === 'no_alert';
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
     * @return array<string, string>
     */
    public function jsonSerialize() : array
    {
        return [
            'oblast' => $this->oblast,
            'status' => $this->status,
        ];
    }
}
