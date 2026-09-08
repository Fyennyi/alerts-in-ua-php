# Threat

The `Threat` model represents a specific threat associated with an ongoing alert. It maps to the new API v1 format that provides granular details about why an alert was triggered.

## Properties

A `Threat` object contains the following data points:

- **Threat Type**: `ThreatType` enum (e.g., `drones`, `cruise_missiles`).
- **Level**: `AlertLevel` enum (`red`, `yellow`, `unknown`).
- **Started At**: DateTime when this specific threat was announced.
- **Source Message**: The original message associated with the threat (e.g., from an official Telegram channel).

## Accessor Methods

```php
public function getThreatType(): ThreatType
public function getLevel(): AlertLevel
public function getStartedAt(): ?\DateTimeInterface
public function getSourceMessage(): ?string
```

## JSON Serialization

The class implements `JsonSerializable`, so you can pass it directly to `json_encode()` or `toArray()` to get a plain array representation.

```php
$json = json_encode($threat);
$array = $threat->toArray();
```
