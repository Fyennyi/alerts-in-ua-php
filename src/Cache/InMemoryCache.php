<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * Simple in-memory cache implementation
 */
class InMemoryCache implements ExpirableCacheInterface
{
    /** @var array<string, array{value: mixed, expires: int}> */
    private array $cache = [];

    public function get(string $key) : mixed
    {
        if (! isset($this->cache[$key])) {
            return null;
        }

        $item = $this->cache[$key];

        if ($item['expires'] > 0 && $item['expires'] < time()) {
            return null;
        }

        return $item['value'];
    }

    public function getStale(string $key) : mixed
    {
        return $this->cache[$key]['value'] ?? null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600) : bool
    {
        $expires = 0;
        if (0 !== $ttl) {
            $expires = time() + $ttl;
        }

        $this->cache[$key] = [
            'value' => $value,
            'expires' => $expires,
        ];

        return true;
    }

    public function delete(string $key) : bool
    {
        unset($this->cache[$key]);

        return true;
    }

    public function clear() : bool
    {
        $this->cache = [];

        return true;
    }

    public function has(string $key) : bool
    {
        return null !== $this->get($key);
    }

    public function keys() : array
    {
        return array_keys($this->cache);
    }

    public function cleanupExpired() : void
    {
        $now = time();
        foreach ($this->cache as $key => $item) {
            if ($item['expires'] > 0 && $item['expires'] < $now) {
                unset($this->cache[$key]);
            }
        }
    }
}
