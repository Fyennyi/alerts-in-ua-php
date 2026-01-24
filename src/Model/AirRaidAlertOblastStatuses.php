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

use Countable;
use IteratorAggregate;
use JsonSerializable;

/**
 * @implements IteratorAggregate<int, AirRaidAlertOblastStatus>
 */
class AirRaidAlertOblastStatuses implements Countable, IteratorAggregate, JsonSerializable
{
    /** @var list<AirRaidAlertOblastStatus> */
    private array $statuses;

    /**
     * Constructor for AirRaidAlertOblastStatuses
     *
     * @param  string  $data  Raw status data string
     * @param  bool  $oblast_level_only  Filter for only oblast level alerts
     */
    public function __construct(string $data, bool $oblast_level_only)
    {
        $this->statuses = [];
        /** @var array<int, string> $oblasts */
        $oblasts = [
            'Автономна Республіка Крим', 'Волинська область', 'Вінницька область', 'Дніпропетровська область',
            'Донецька область', 'Житомирська область', 'Закарпатська область', 'Запорізька область',
            'Івано-Франківська область', 'м. Київ', 'Київська область', 'Кіровоградська область',
            'Луганська область', 'Львівська область', 'Миколаївська область', 'Одеська область',
            'Полтавська область', 'Рівненська область', 'м. Севастополь', 'Сумська область',
            'Тернопільська область', 'Харківська область', 'Херсонська область', 'Хмельницька область',
            'Черкаська область', 'Чернівецька область', 'Чернігівська область',
        ];
        $statuses = str_split($data);

        foreach ($oblasts as $i => $oblast) {
            if (! isset($statuses[$i])) {
                break;
            }

            $status = $statuses[$i];
            $this->statuses[] = new AirRaidAlertOblastStatus($oblast, $status, $oblast_level_only);
        }
    }

    /**
     * Get all oblast statuses
     *
     * @return list<AirRaidAlertOblastStatus> Array of AirRaidAlertOblastStatus objects
     */
    public function getStatuses() : array
    {
        return $this->statuses;
    }

    /**
     * Filter statuses by a specific status value
     *
     * @param  string  $status  Status to filter by ('no_alert', 'active', 'partly')
     * @return array<int, AirRaidAlertOblastStatus> Filtered list of status objects
     */
    public function filterByStatus(string $status) : array
    {
        return array_filter($this->statuses, fn (AirRaidAlertOblastStatus $s) => $s->getStatus() === $status);
    }

    /**
     * Get all oblasts with active alerts
     *
     * @return array<int, AirRaidAlertOblastStatus> List of oblast status objects with active alerts
     */
    public function getActiveAlertOblasts() : array
    {
        return $this->filterByStatus('active');
    }

    /**
     * Get all oblasts with partly active alerts
     *
     * @return array<int, AirRaidAlertOblastStatus> List of oblast status objects with partly active alerts
     */
    public function getPartlyActiveAlertOblasts() : array
    {
        return $this->filterByStatus('partly');
    }

    /**
     * Get all oblasts with no alerts
     *
     * @return array<int, AirRaidAlertOblastStatus> List of oblast status objects with no alerts
     */
    public function getNoAlertOblasts() : array
    {
        return $this->filterByStatus('no_alert');
    }

    /**
     * @return \Traversable<int, AirRaidAlertOblastStatus>
     */
    public function getIterator() : \Traversable
    {
        return new \ArrayIterator($this->statuses);
    }

    public function count() : int
    {
        return count($this->statuses);
    }

    /**
     * @return list<AirRaidAlertOblastStatus>
     */
    public function jsonSerialize(): array
    {
        return $this->statuses;
    }

    /**
     * Get string representation of the oblast statuses
     *
     * @return string JSON representation
     */
    public function __toString() : string
    {
        try {
            return json_encode($this, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            error_log('Failed to serialize AirRaidAlertOblastStatuses to string: ' . $e->getMessage());
            return '';
        }
    }
}
