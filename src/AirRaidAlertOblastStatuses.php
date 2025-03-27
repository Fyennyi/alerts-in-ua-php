<?php

namespace AlertsUA;

class AirRaidAlertOblastStatuses
{
    private $statuses;

    public function __construct($data, $oblast_level_only)
    {
        $this->statuses = [];
        $oblasts = [
            'Автономна Республіка Крим', 'Волинська область', 'Вінницька область', 'Дніпропетровська область',
            'Донецька область', 'Житомирська область', 'Закарпатська область', 'Запорізька область',
            'Івано-Франківська область', 'м. Київ', 'Київська область', 'Кіровоградська область',
            'Луганська область', 'Львівська область', 'Миколаївська область', 'Одеська область',
            'Полтавська область', 'Рівненська область', 'м. Севастополь', 'Сумська область',
            'Тернопільська область', 'Харківська область', 'Херсонська область', 'Хмельницька область',
            'Черкаська область', 'Чернівецька область', 'Чернігівська область',
        ];

        foreach (str_split($data) as $index => $status) {
            if ($oblast_level_only && 'A' !== $status) {
                continue;
            }
            $this->statuses[] = new AirRaidAlertOblastStatus($oblasts[$index], $status);
        }
    }

    public function getStatuses()
    {
        return $this->statuses;
    }
}
