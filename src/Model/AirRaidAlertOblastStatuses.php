<?php

namespace Fyennyi\AlertsInUa\Model;

class AirRaidAlertOblastStatuses
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
        $max_index = min(count($oblasts), count($statuses)) - 1;

        for ($i = 0; $i <= $max_index; $i++) {
            if (! isset($oblasts[$i], $statuses[$i])) {
                continue;
            }

            if ($oblast_level_only && 'A' !== $statuses[$i]) {
                continue;
            }

            $this->statuses[] = new AirRaidAlertOblastStatus($oblasts[$i], $statuses[$i]);
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
}
