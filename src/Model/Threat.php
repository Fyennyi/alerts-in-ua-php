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

use Fyennyi\AlertsInUa\Model\Enum\AlertLevel;
use Fyennyi\AlertsInUa\Model\Enum\ThreatType;
use Fyennyi\AlertsInUa\Util\UaDateParser;
use JsonSerializable;

class Threat implements JsonSerializable
{
    private ThreatType $threat_type;
    private AlertLevel $level;
    private ?\DateTimeInterface $started_at;
    private ?string $source_message;

    /**
     * Constructor for Threat
     *
     * @param array<mixed, mixed> $data Raw threat data from API
     */
    public function __construct(array $data)
    {
        $this->threat_type = ThreatType::fromString(isset($data['threat_type']) && is_string($data['threat_type']) ? $data['threat_type'] : null);
        $this->level = AlertLevel::fromString(isset($data['level']) && is_string($data['level']) ? $data['level'] : null);
        $this->started_at = isset($data['started_at']) && is_string($data['started_at']) ? UaDateParser::parseDate($data['started_at']) : null;
        $this->source_message = isset($data['source_message']) && is_string($data['source_message']) ? $data['source_message'] : null;
    }

    /**
     * Get threat type
     *
     * @return ThreatType Threat type enum
     */
    public function getThreatType() : ThreatType
    {
        return $this->threat_type;
    }

    /**
     * Get threat level
     *
     * @return AlertLevel Threat level enum
     */
    public function getLevel() : AlertLevel
    {
        return $this->level;
    }

    /**
     * Get threat start timestamp
     *
     * @return \DateTimeInterface|null Threat start date and time in Kyiv timezone or null if not set
     */
    public function getStartedAt() : ?\DateTimeInterface
    {
        return $this->started_at;
    }

    /**
     * Get original source message
     *
     * @return string|null Source message or null if not specified
     */
    public function getSourceMessage() : ?string
    {
        return $this->source_message;
    }

    /**
     * Get threat as array representation
     *
     * @return array<string, mixed> Array representation of the threat
     */
    public function toArray() : array
    {
        return [
            'threat_type' => $this->threat_type->value,
            'level' => $this->level->value,
            'started_at' => $this->started_at?->format('Y-m-d H:i:s'),
            'source_message' => $this->source_message,
        ];
    }

    /**
     * Get threat as JSON representation
     *
     * @return string            JSON representation of the threat
     * @throws \RuntimeException If JSON encoding fails
     */
    public function toJson() : string
    {
        try {
            return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new \RuntimeException('Failed to encode threat to JSON: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize() : array
    {
        return $this->toArray();
    }
}
