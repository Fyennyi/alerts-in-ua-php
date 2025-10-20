<?php

namespace Tests\Unit\Cache;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class SmartCacheManagerTest extends TestCase
{
    private $cacheMock;
    private SmartCacheManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cacheMock = $this->createMock(TagAwareCacheInterface::class);
        $this->manager = new SmartCacheManager($this->cacheMock);
    }

    public function testConstructorWorksWithoutCache()
    {
        $manager = new SmartCacheManager();
        $this->assertInstanceOf(SmartCacheManager::class, $manager);
    }

    public function testGetOrSetReturnsCachedValueOnHit()
    {
        $callback = fn() => 'new_data';

        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('test_key', $this->isType('callable'))
            ->willReturn('cached_data');

        $result = $this->manager->getOrSet('test_key', $callback);

        $this->assertEquals('cached_data', $result);
    }

    public function testGetOrSetExecutesCallbackOnMissAndTagsItem()
    {
        $callback = fn() => 'new_data';

        // Simulate a cache miss by having the mock execute the callback.
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('test_key', $this->isType('callable'))
            ->willReturnCallback(
                function ($key, $callable) {
                    $itemMock = $this->createMock(ItemInterface::class);
                    $itemMock->expects($this->once())->method('expiresAfter')->with(500);
                    $itemMock->expects($this->once())->method('tag')->with('custom_type');
                    $itemMock->expects($this->once())->method('getKey')->willReturn($key);
                    return $callable($itemMock);
                }
            );

        $this->manager->setTtl('custom_type', 500);
        $result = $this->manager->getOrSet('test_key', $callback, 'custom_type');

        $this->assertEquals('new_data', $result);
    }

    public function testGetOrSetBypassesCacheWhenDisabled()
    {
        $this->cacheMock->expects($this->never())->method('get');

        $callback = fn() => 'new_data';
        $result = $this->manager->getOrSet('test_key', $callback, 'default', false);

        $this->assertEquals('new_data', $result);
    }

    public function testInvalidateTags()
    {
        $tags = ['alerts', 'history'];
        $this->cacheMock->expects($this->once())
            ->method('invalidateTags')
            ->with($tags);

        $this->manager->invalidateTags($tags);
    }

    public function testSetLastModified()
    {
        $this->cacheMock->expects($this->once())->method('delete')->with('key.last_modified');
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.last_modified', $this->isType('callable'))
            ->willReturnCallback(
                function ($key, $callable) {
                    $itemMock = $this->createMock(ItemInterface::class);
                    $itemMock->expects($this->once())->method('expiresAfter')->with(86400);
                    $itemMock->expects($this->once())->method('tag')->with('key');
                    return $callable($itemMock);
                }
            );

        $this->manager->setLastModified('key', 'timestamp');
    }

    public function testGetLastModified()
    {
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.last_modified', $this->isType('callable'))
            ->willReturn('timestamp');

        $this->assertEquals('timestamp', $this->manager->getLastModified('key'));
    }

    public function testStoreProcessedData()
    {
        $data = ['a' => 1];
        $this->cacheMock->expects($this->once())->method('delete')->with('key.processed');
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.processed', $this->isType('callable'))
             ->willReturnCallback(
                function ($key, $callable) {
                    $itemMock = $this->createMock(ItemInterface::class);
                    $itemMock->expects($this->once())->method('expiresAfter')->with(86400);
                    $itemMock->expects($this->once())->method('tag')->with('key');
                    return $callable($itemMock);
                }
            );

        $this->manager->storeProcessedData('key', $data);
    }

    public function testGetCachedData()
    {
        $data = ['a' => 1];
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.processed', $this->isType('callable'))
            ->willReturn($data);

        $this->assertEquals($data, $this->manager->getCachedData('key'));
    }
}