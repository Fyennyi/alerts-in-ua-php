<?php

namespace Fyennyi\AlertsInUa\Model;

use Countable;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, AirRaidAlertOblastStatus>
 */
class AirRaidAlertOblastStatuses implements IteratorAggregate, Countable
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
            $oblastStatus = new AirRaidAlertOblastStatus($oblast, $status, $oblast_level_only);

            if ($oblast_level_only && ! $oblastStatus->isActive()) {
                continue;
            }

            $this->statuses[] = $oblastStatus;
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

    public function __toString() : string
    {
        $json = json_encode($this->statuses);
        if ($json === false) {
            return ''; // Or throw an exception
        }
        return $json;
    }
}
