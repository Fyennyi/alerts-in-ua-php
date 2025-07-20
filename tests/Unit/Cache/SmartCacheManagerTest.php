<?php

namespace Tests\Unit\Cache;

use Fyennyi\AlertsInUa\Cache\CacheInterface;
use Fyennyi\AlertsInUa\Cache\ExpirableCacheInterface;
use Fyennyi\AlertsInUa\Cache\InMemoryCache;
use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use PHPUnit\Framework\TestCase;

class SmartCacheManagerTest extends TestCase
{
    private $cacheMock;

    private SmartCacheManager $manager;

    protected function setUp() : void
    {
        parent::setUp();
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->manager = new SmartCacheManager($this->cacheMock);
    }

    public function testConstructorUsesInMemoryCacheByDefault()
    {
        $manager = new SmartCacheManager();
        $this->assertInstanceOf(SmartCacheManager::class, $manager);
    }

    public function testGetOrSetReturnsCachedValueIfExists()
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('cached_data');

        $callback = fn() => 'new_data';

        $result = $this->manager->getOrSet('test_key', $callback);

        $this->assertEquals('cached_data', $result);
    }

    public function testGetOrSetExecutesCallbackOnCacheMiss()
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn(null);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'new_data', 300); // Default TTL

        $callback = fn() => 'new_data';

        $result = $this->manager->getOrSet('test_key', $callback);

        $this->assertEquals('new_data', $result);
    }

    public function testGetOrSetUsesCustomTtl()
    {
        $this->manager->setTtl('custom_type', 500);

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn(null);

        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('test_key', 'new_data', 500);

        $callback = fn() => 'new_data';

        $this->manager->getOrSet('test_key', $callback, 'custom_type');
    }

    public function testGetOrSetBypassesCacheWhenDisabled()
    {
        $this->cacheMock->expects($this->never())->method('get');
        $this->cacheMock->expects($this->never())->method('set');

        $callback = fn() => 'new_data';

        $result = $this->manager->getOrSet('test_key', $callback, 'default', false);

        $this->assertEquals('new_data', $result);
    }

    public function testInvalidatePattern()
    {
        $keys = ['alerts/1', 'alerts/2', 'other/1'];
        $this->cacheMock->expects($this->once())->method('keys')->willReturn($keys);

        $this->cacheMock->expects($this->exactly(6))
            ->method('delete')
            ->with($this->matchesRegularExpression('/^alerts\/\d+(\.last_modified|\.processed)?$/'));

        $this->manager->invalidatePattern('alerts/*');
    }
    
    public function testGetStaleData()
    {
        $this->cacheMock->expects($this->once())
            ->method('getStale')
            ->with('stale_key')
            ->willReturn('stale_data');
            
        $result = $this->manager->getStaleData('stale_key');
        $this->assertEquals('stale_data', $result);
    }

    public function testLastModifiedMethods()
    {
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('key.last_modified', 'timestamp', 86400);
        $this->manager->setLastModified('key', 'timestamp');

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.last_modified')
            ->willReturn('timestamp');
        $this->assertEquals('timestamp', $this->manager->getLastModified('key'));
    }

    public function testProcessedDataMethods()
    {
        $data = ['a' => 1];
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with('key.processed', $data, 86400);
        $this->manager->storeProcessedData('key', $data);

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.processed')
            ->willReturn($data);
        $this->assertEquals($data, $this->manager->getCachedData('key'));
    }

    public function testCleanupIsCalledForExpirableCache()
    {
        $expirableCacheMock = $this->createMock(ExpirableCacheInterface::class);
        $manager = new SmartCacheManager($expirableCacheMock);

        $expirableCacheMock->expects($this->once())->method('get')->willReturn(null);
        $expirableCacheMock->expects($this->once())->method('cleanupExpired');

        $manager->getOrSet('test', fn() => 'data');
    }
}
