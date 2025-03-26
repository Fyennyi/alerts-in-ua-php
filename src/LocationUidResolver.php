<?php

namespace AlertsUA;

class LocationUidResolver
{
    private $uid_to_location = [
        3 => 'Хмельницька область',
        4 => 'Вінницька область',
        5 => 'Рівненська область',
        8 => 'Волинська область',
        9 => 'Дніпропетровська область',
        10 => 'Житомирська область',
        11 => 'Закарпатська область',
        12 => 'Запорізька область',
        13 => 'Івано-Франківська область',
        14 => 'Київська область',
        15 => 'Кіровоградська область',
        16 => 'Луганська область',
        17 => 'Миколаївська область',
        18 => 'Одеська область',
        19 => 'Полтавська область',
        20 => 'Сумська область',
        21 => 'Тернопільська область',
        22 => 'Харківська область',
        23 => 'Херсонська область',
        24 => 'Черкаська область',
        25 => 'Чернігівська область',
        26 => 'Чернівецька область',
        27 => 'Львівська область',
        28 => 'Донецька область',
        29 => 'Автономна Республіка Крим',
        30 => 'м. Севастополь',
        31 => 'м. Київ',
    ];

    private $location_to_uid;

    public function __construct()
    {
        $this->location_to_uid = array_flip($this->uid_to_location);
    }

    public function resolveUid($location_title)
    {
        return $this->location_to_uid[$location_title] ?? 'Unknown UID';
    }

    public function resolveLocationTitle($uid)
    {
        return $this->uid_to_location[$uid] ?? 'Unknown location';
    }
}
