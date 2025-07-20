<?php

namespace Tests\Unit\Cache;

use Fyennyi\AlertsInUa\Cache\InMemoryCache;
use PHPUnit\Framework\TestCase;

class InMemoryCacheTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp() : void
    {
        parent::setUp();
        $this->cache = new InMemoryCache();
    }

    public function testSetAndGet()
    {
        $this->assertTrue($this->cache->set('key1', 'value1', 60));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetNonExistent()
    {
        $this->assertNull($this->cache->get('non_existent_key'));
    }

    public function testGetExpired()
    {
        $this->cache->set('key_expired', 'value_expired', -1);
        $this->assertNull($this->cache->get('key_expired'));
    }

    public function testGetStale()
    {
        $this->cache->set('key_stale', 'value_stale', -1); // Expired
        $this->assertEquals('value_stale', $this->cache->getStale('key_stale'));
        $this->assertNull($this->cache->getStale('non_existent'));
    }

    public function testDelete()
    {
        $this->cache->set('key_to_delete', 'value_to_delete');
        $this->assertTrue($this->cache->delete('key_to_delete'));
        $this->assertNull($this->cache->get('key_to_delete'));
    }

    public function testClear()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $this->assertTrue($this->cache->clear());
        $this->assertCount(0, $this->cache->keys());
    }

    public function testHas()
    {
        $this->cache->set('key_has', 'value_has');
        $this->assertTrue($this->cache->has('key_has'));
        $this->assertFalse($this->cache->has('non_existent_key'));
    }

    public function testKeys()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        $keys = $this->cache->keys();
        $this->assertCount(2, $keys);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
    }

    public function testCleanupExpired()
    {
        $this->cache->set('key_valid', 'value_valid', 3600);
        $this->cache->set('key_expired', 'value_expired', -1); // Expired

        $this->cache->cleanupExpired();

        $this->assertTrue($this->cache->has('key_valid'));
        $this->assertFalse($this->cache->has('key_expired'));
    }

    public function testSetWithZeroTtlIsPermanent()
    {
        $this->cache->set('permanent_key', 'permanent_value', 0);
        $this->assertEquals('permanent_value', $this->cache->get('permanent_key'));
        // It's hard to test permanence without waiting, but we can check the expiry time
        $this->cache->cleanupExpired();
        $this->assertTrue($this->cache->has('permanent_key'));
    }
}
