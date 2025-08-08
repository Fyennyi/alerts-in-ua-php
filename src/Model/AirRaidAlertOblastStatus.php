<?php

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

    public function __toString() : string
    {
        return sprintf("%s:%s", $this->status, $this->oblast);
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'oblast' => $this->oblast,
            'status' => $this->status,
        ];
    }
}
