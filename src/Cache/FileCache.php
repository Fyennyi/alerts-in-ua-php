<?php

namespace Fyennyi\AlertsInUa\Cache;

/**
 * File-based persistent cache implementation
 */
class FileCache implements CacheInterface
{
    private string $cache_dir;

    public function __construct(string $cache_dir = '/tmp/alerts_cache')
    {
        $this->cacheDir = $cache_dir;
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

        $data = file_get_contents($filename);
        if ($data === false) {
            return null;
        }

        $item = unserialize($data);
        if (! is_array($item) || ! isset($item['value'], $item['expires'])) {
            return null;
        }

        if ($item['expires'] > 0 && $item['expires'] < time()) {
            unlink($filename);

            return null;
        }

        return $item['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600) : bool
    {
        $filename = $this->getCacheFilename($key);
        $data = serialize([
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ]);

        return file_put_contents($filename, $data) !== false;
    }

    public function delete(string $key) : bool
    {
        $filename = $this->getCacheFilename($key);
        return file_exists($filename) ? unlink($filename) : true;
    }

    public function clear() : bool
    {
        $files = glob($this->cache_dir . '/*.cache');
        if ($files === false) {
            return false;
        }

        foreach ($files as $file) {
            @unlink($file);
        }

        return true;
    }

    public function has(string $key) : bool
    {
        return null !== $this->get($key);
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
}
