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

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, AirRaidAlertStatus>
 * @implements ArrayAccess<int, AirRaidAlertStatus>
 */
class AirRaidAlertStatuses implements IteratorAggregate, Countable, ArrayAccess
{
    /** @var array<int, AirRaidAlertStatus> */
    private array $statuses;

    /** @var array<int|string, AirRaidAlertStatus> */
    private array $uid_cache = [];

    /**
     * AirRaidAlertStatuses constructor
     *
     * @param  array<int, AirRaidAlertStatus>  $statuses  List of air raid alert status objects
     */
    public function __construct(array $statuses)
    {
        $this->statuses = $statuses;
        foreach ($statuses as $status) {
            if ($status->getUid() !== null) {
                $this->uid_cache[$status->getUid()] = $status;
            }
        }
    }

    /**
     * Filter statuses by a specific status value
     *
     * @param  string  $status  Status to filter by ('no_alert', 'active', 'partly')
     * @return array<int, AirRaidAlertStatus> Filtered list of status objects
     */
    public function filterByStatus(string $status) : array
    {
        return array_filter($this->statuses, fn (AirRaidAlertStatus $s) => $s->getStatus() === $status);
    }

    /**
     * Get all statuses with active alerts
     *
     * @return array<int, AirRaidAlertStatus> List of status objects with active alerts
     */
    public function getActiveAlertStatuses() : array
    {
        return $this->filterByStatus('active');
    }

    /**
     * Get all statuses with partial alerts
     *
     * @return array<int, AirRaidAlertStatus> List of status objects with partial alerts
     */
    public function getPartlyActiveAlertStatuses() : array
    {
        return $this->filterByStatus('partly');
    }

    /**
     * Get all statuses with no alerts
     *
     * @return array<int, AirRaidAlertStatus> List of status objects with no alerts
     */
    public function getNoAlertStatuses() : array
    {
        return $this->filterByStatus('no_alert');
    }

    /**
     * Get status by UID using cached lookup
     *
     * @param  int  $uid  The UID to look up
     * @return AirRaidAlertStatus|null The status object if found, null otherwise
     */
    public function getStatus(int $uid) : ?AirRaidAlertStatus
    {
        return $this->uid_cache[$uid] ?? null;
    }

    public function getIterator() : Traversable
    {
        return new \ArrayIterator($this->statuses);
    }

    public function count() : int
    {
        return count($this->statuses);
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->statuses[$offset]);
    }

    public function offsetGet($offset) : ?AirRaidAlertStatus
    {
        return $this->statuses[$offset] ?? null;
    }

    public function offsetSet($offset, $value) : void
    {
        // This is a read-only collection
    }

    public function offsetUnset($offset) : void
    {
        // This is a read-only collection
    }
}
