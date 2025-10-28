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

class AirRaidAlertStatusResolver
{
    private const STATUS_MAPPING = [
        'N' => 'no_alert',
        'A' => 'active',
        'P' => 'partly',
        ' ' => 'undefined',
    ];

    /**
     * Resolves a single status character to its corresponding status value
     *
     * @param  string  $status_char  The character from the API response
     * @return string The resolved status value ('no_alert', 'active', or 'partly')
     */
    public static function resolveStatusChar(string $status_char) : string
    {
        return self::STATUS_MAPPING[$status_char] ?? 'no_alert';
    }

    /**
     * Resolves a complete status string to a list of status dictionaries
     * Filters out statuses with 'undefined' status
     *
     * @param  string  $status_string  The complete status string from the API
     * @param  array<int, string>  $uid_to_location_mapping  Mapping of UID to location title
     * @return array<int, array{uid: int, location_title: string, status: string}> List of resolved statuses
     */
    public static function resolveStatusString(string $status_string, array $uid_to_location_mapping) : array
    {
        $resolved_statuses = [];
        $status_string_length = strlen($status_string);

        for ($uid = 0; $uid < $status_string_length; ++$uid) {
            $status_char = $status_string[$uid];
            $status = self::resolveStatusChar($status_char);

            if ($status !== 'undefined') {
                $location_title = $uid_to_location_mapping[$uid] ?? "Локація #{$uid}";
                $resolved_statuses[] = [
                    'uid' => $uid,
                    'location_title' => $location_title,
                    'status' => $status,
                ];
            }
        }

        return $resolved_statuses;
    }
}
