<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * File-based persistent cache implementation
 */
class FileCache implements ExpirableCacheInterface
{
    private string $cache_dir;

    public function __construct(?string $cache_dir = null)
    {
        if (null === $cache_dir) {
            $cache_dir = sys_get_temp_dir() . '/alerts_cache';
        }

        $this->cache_dir = $cache_dir;

        if (! is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }

    public function get(string $key) : mixed
    {
        $filename = $this->getCacheFilename($key);
        if (! file_exists($filename)) {
            return null;
        }

        $data = @file_get_contents($filename);
        if (false === $data) {
            return null;
        }

        $item = @unserialize($data);
        if (! is_array($item) || ! isset($item['value'], $item['expires'])) {
            return null;
        }

        if ($item['expires'] > 0 && $item['expires'] < time()) {
            return null;
        }

        return $item['value'];
    }

    public function getStale(string $key) : mixed
    {
        $filename = $this->getCacheFilename($key);
        if (! file_exists($filename)) {
            return null;
        }

        $data = @file_get_contents($filename);
        if (false === $data) {
            return null;
        }

        $item = @unserialize($data);
        if (! is_array($item) || ! isset($item['value'])) {
            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600) : bool
    {
        $filename = $this->getCacheFilename($key);

        $expires = 0;
        if (0 !== $ttl) {
            $expires = time() + $ttl;
        }

        $data = serialize([
            'key' => $key,
            'value' => $value,
            'expires' => $expires,
        ]);

        return false !== @file_put_contents($filename, $data);
    }

    public function delete(string $key) : bool
    {
        $filename = $this->getCacheFilename($key);
        return file_exists($filename) ? unlink($filename) : true;
    }

    public function clear() : bool
    {
        $files = @scandir($this->cache_dir);
        if (false === $files) {
            return false;
        }

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'cache') {
                @unlink($this->cache_dir . '/' . $file);
            }
        }

        return true;
    }

    public function has(string $key) : bool
    {
        return null !== $this->get($key);
    }

    public function keys() : array
    {
        $files = @scandir($this->cache_dir);
        if (false === $files) {
            return [];
        }

        $keys = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file_path = $this->cache_dir . '/' . $file;
            $data = @file_get_contents($file_path);
            if (false === $data) {
                continue;
            }

            $item = @unserialize($data);
            if (! is_array($item) || ! isset($item['key']) || ! is_string($item['key'])) {
                continue;
            }

            $keys[] = $item['key'];
        }

        /** @var list<string> */
        return $keys;
    }

    /**
     * Generate the cache filename for a given key
     *
     * @param  string  $key  Cache key
     * @return string Full path to cache file
     */
    private function getCacheFilename(string $key) : string
    {
        return $this->cache_dir . '/' . md5($key) . '.cache';
    }

    public function cleanupExpired() : void
    {
        $files = @scandir($this->cache_dir);

        if (false === $files) {
            return;
        }

        $now = time();

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $file_path = $this->cache_dir . '/' . $file;
            $data = @file_get_contents($file_path);
            if (false === $data) {
                continue;
            }

            $item = @unserialize($data);
            if (! is_array($item) || ! isset($item['expires'])) {
                continue;
            }

            if ($item['expires'] > 0 && $item['expires'] < $now) {
                @unlink($file_path);
            }
        }
    }
}
