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

class AirRaidAlertStatus implements JsonSerializable
{
    private string $location_title;

    private string $status;

    private ?int $uid;

    /**
     * AirRaidAlertStatus constructor
     *
     * @param  string  $location_title  The title/name of the location
     * @param  string  $status  The alert status ('no_alert', 'active', 'partly')
     * @param  int|null  $uid  The UID of the location
     */
    public function __construct(string $location_title, string $status, ?int $uid = null)
    {
        $this->location_title = $location_title;
        $this->status = $status;
        $this->uid = $uid;
    }

    /**
     * Get the location title
     *
     * @return string The location title
     */
    public function getLocationTitle() : string
    {
        return $this->location_title;
    }

    /**
     * Get the alert status
     *
     * @return string The alert status
     */
    public function getStatus() : string
    {
        return $this->status;
    }

    /**
     * Get the location UID
     *
     * @return int|null The location UID
     */
    public function getUid() : ?int
    {
        return $this->uid;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize() : array
    {
        return [
            'location_title' => $this->location_title,
            'status' => $this->status,
            'uid' => $this->uid,
        ];
    }

    /**
     * Get string representation of the alert status
     *
     * @return string JSON representation
     */
    public function __toString() : string
    {
        try {
            return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Failed to serialize AirRaidAlertStatus to string: ' . $e->getMessage());
            return '';
        }
    }
}
