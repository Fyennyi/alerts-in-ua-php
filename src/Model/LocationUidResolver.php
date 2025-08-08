<?php

namespace Fyennyi\AlertsInUa\Model;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;

class LocationUidResolver
{
    /** @var array<int, string> */
    private array $uid_to_location = [];

    /** @var array<string, int> */
    private array $location_to_uid = [];

    /**
     * Constructor for LocationUidResolver
     * Loads location data from a JSON file and initializes the UID mappings
     *
     * @throws \RuntimeException If the locations file is missing or cannot be read
     */
    public function __construct()
    {
        $json_path = __DIR__ . '/locations.json';

        if (! file_exists($json_path)) {
            throw new \RuntimeException("Locations data file not found at {$json_path}");
        }

        $json_content = file_get_contents($json_path);
        if (false === $json_content) {
            throw new \RuntimeException("Could not read locations data file from {$json_path}");
        }

        /** @var array<int, string>|null $locations */
        $locations = json_decode($json_content, true);

        if (! is_array($locations)) {
            throw new \RuntimeException("Failed to decode locations JSON from {$json_path}");
        }

        $this->uid_to_location = $locations;
        $this->location_to_uid = array_flip($this->uid_to_location);
    }

    /**
     * Resolve location title to UID
     *
     * @param  string  $location_title  Location title to resolve
     * @return int UID for the location
     *
     * @throws InvalidParameterException If location is not found
     */
    public function resolveUid(string $location_title) : int
    {
        if (! isset($this->location_to_uid[$location_title])) {
            throw new InvalidParameterException("Unknown location: {$location_title}");
        }

        return $this->location_to_uid[$location_title];
    }

    /**
     * Resolve UID to location title
     *
     * @param  int  $uid  UID to resolve
     * @return string Location title
     *
     * @throws InvalidParameterException If UID is not found
     */
    public function resolveLocationTitle(int $uid) : string
    {
        if (! isset($this->uid_to_location[$uid])) {
            throw new InvalidParameterException("Unknown UID: {$uid}");
        }
        
        return $this->uid_to_location[$uid];
    }

    /**
     * Returns the entire UID to location mapping array
     *
     * @return array<int, string> The mapping of UIDs to location names
     */
    public function getUidToLocationMapping() : array
    {
        return $this->uid_to_location;
    }
}
