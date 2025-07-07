<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * Simple in-memory cache implementation
 */
class InMemoryCache implements CacheInterface
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
            unset($this->cache[$key]);

            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600) : bool
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
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
}
