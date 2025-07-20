<?php

namespace Fyennyi\AlertsInUa\Tests\Unit\Cache;

use Fyennyi\AlertsInUa\Cache\FileCache;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    private vfsStreamDirectory $root;

    private string $cacheDir;

    private FileCache $cache;

    protected function setUp() : void
    {
        $this->root = vfsStream::setup('cache');
        $this->cacheDir = $this->root->url();
        $this->cache = new FileCache($this->cacheDir);
    }

    public function testConstructorCreatesDirectory()
    {
        $newCacheDir = $this->cacheDir . '/new_dir';
        $this->assertFalse($this->root->hasChild('new_dir'));
        new FileCache($newCacheDir);
        $this->assertTrue($this->root->hasChild('new_dir'));
    }

    public function testSetAndGet()
    {
        $this->assertTrue($this->cache->set('key1', 'value1', 60));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testSetWithZeroTtlIsPermanent()
    {
        $this->cache->set('permanent_key', 'permanent_value', 0);
        // To test this, we need to inspect the file content
        $fileName = md5('permanent_key') . '.cache';
        $data = unserialize($this->root->getChild($fileName)->getContent());
        $this->assertEquals(0, $data['expires']);
        $this->assertEquals('permanent_value', $this->cache->get('permanent_key'));
    }

    public function testGetNonExistent()
    {
        $this->assertNull($this->cache->get('non_existent_key'));
    }

    public function testGetExpired()
    {
        $this->cache->set('key_expired', 'value_expired', -1);
        $this->assertNull($this->cache->get('key_expired'));
        // Assert that the file still exists
        $this->assertTrue($this->root->hasChild(md5('key_expired') . '.cache'));
    }

    public function testGetWithCorruptedData()
    {
        $fileName = md5('corrupted') . '.cache';
        vfsStream::newFile($fileName)->at($this->root)->withContent('corrupted data');
        $this->assertNull($this->cache->get('corrupted'));
    }

    public function testGetStale()
    {
        $this->cache->set('key_stale', 'value_stale', -1); // Expired
        $this->assertEquals('value_stale', $this->cache->getStale('key_stale'));
    }

    public function testGetStaleWithCorruptedData()
    {
        $fileName = md5('corrupted_stale') . '.cache';
        vfsStream::newFile($fileName)->at($this->root)->withContent('corrupted data');
        $this->assertNull($this->cache->getStale('corrupted_stale'));
    }

    public function testDelete()
    {
        $this->cache->set('key_to_delete', 'value_to_delete');
        $this->assertTrue($this->cache->delete('key_to_delete'));
        $this->assertNull($this->cache->get('key_to_delete'));
    }

    public function testDeleteNonExistentKey()
    {
        $this->assertTrue($this->cache->delete('non_existent_key'));
    }

    public function testClear()
    {
        $this->cache->set('key1', 'value1');
        $this->cache->set('key2', 'value2');
        vfsStream::newFile('not_a_cache_file.txt')->at($this->root)->withContent('some data');
        $this->assertTrue($this->cache->clear());
        $this->assertNull($this->cache->get('key1'));
        $this->assertNull($this->cache->get('key2'));
        $this->assertCount(1, $this->root->getChildren()); // The non-cache file should remain
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

    public function testKeysWithCorruptedFile()
    {
        $this->cache->set('key1', 'value1');
        vfsStream::newFile('corrupted.cache')->at($this->root)->withContent('corrupted');
        $keys = $this->cache->keys();
        $this->assertCount(1, $keys);
        $this->assertContains('key1', $keys);
    }

    public function testCleanupExpired()
    {
        $this->cache->set('key_valid', 'value_valid', 3600);
        $this->cache->set('key_expired', 'value_expired', -1); // Expired

        $this->cache->cleanupExpired();

        $this->assertTrue($this->cache->has('key_valid'));
        $this->assertFalse($this->root->hasChild(md5('key_expired') . '.cache'));
    }

    public function testSetFailsOnUnwritableDirectory()
    {
        $this->root->chmod(0444); // Make directory read-only
        $this->assertFalse($this->cache->set('key', 'value'));
        $this->root->chmod(0755); // Restore permissions
    }

    public function testClearOnUnreadableDirectory()
    {
        // This is tricky to test reliably with vfsStream as it might not prevent scandir
        // However, we can assert it doesn't throw an exception and returns false.
        $unreadableDir = vfsStream::newDirectory('unreadable', 0000)->at($this->root);
        $cache = new FileCache($unreadableDir->url());
        $this->assertFalse($cache->clear());
    }

    public function testKeysOnUnreadableDirectory()
    {
        $unreadableDir = vfsStream::newDirectory('unreadable_keys', 0000)->at($this->root);
        $cache = new FileCache($unreadableDir->url());
        $this->assertEquals([], $cache->keys());
    }

    public function testCleanupOnUnreadableDirectory()
    {
        $unreadableDir = vfsStream::newDirectory('unreadable_cleanup', 0000)->at($this->root);
        $cache = new FileCache($unreadableDir->url());
        $cache->cleanupExpired(); // Should not throw an exception
        $this->assertTrue(true);
    }

    public function testGetWithUnreadableFile()
    {
        $key = 'unreadable_key';
        $fileName = md5($key) . '.cache';
        $file = vfsStream::newFile($fileName, 0000)->at($this->root)->withContent('data');

        $this->assertNull($this->cache->get($key));
    }

    public function testGetStaleWithUnreadableFile()
    {
        $key = 'unreadable_stale';
        $fileName = md5($key) . '.cache';
        $file = vfsStream::newFile($fileName, 0000)->at($this->root)->withContent('data');

        $this->assertNull($this->cache->getStale($key));
    }
}
