<?php

namespace Tests\Unit\Cache;

use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\RejectedPromise;
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
            ->with('test_key', $this->isCallable())
            ->willReturn('cached_data');

        $result = $this->manager->getOrSet('test_key', $callback);

        $this->assertEquals('cached_data', $result->wait());
    }

    public function testGetOrSetExecutesCallbackOnMiss()
    {
        $callback = fn() => Create::promiseFor('new_data');

        // 1. First 'get' call to check the cache (miss)
        $this->cacheMock->expects($this->atLeastOnce())
            ->method('get')
            ->with('test_key', $this->isCallable())
            ->willReturn(null);

        $result = $this->manager->getOrSet('test_key', $callback, 'custom_type');

        // Assert that the final result is the one from the callback
        $this->assertEquals('new_data', $result->wait());
    }

    public function testGetOrSetBypassesCacheWhenDisabled()
    {
        $this->cacheMock->expects($this->never())->method('get');

        $callback = fn() => 'new_data';
        $result = $this->manager->getOrSet('test_key', $callback, 'default', false);

        $this->assertEquals('new_data', $result->wait());
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
            ->with('key.last_modified', $this->isCallable())
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
            ->with('key.last_modified', $this->isCallable())
            ->willReturn('timestamp');

        $this->assertEquals('timestamp', $this->manager->getLastModified('key'));
    }

    public function testStoreProcessedData()
    {
        $data = ['a' => 1];
        $this->cacheMock->expects($this->once())->method('delete')->with('key.processed');
        $this->cacheMock->expects($this->once())
            ->method('get')
            ->with('key.processed', $this->isCallable())
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
            ->with('key.processed', $this->isCallable())
            ->willReturn($data);

        $this->assertEquals($data, $this->manager->getCachedData('key'));
    }

    public function testRateLimitingReturnsRejectedPromise()
    {
        $this->cacheMock->method('get')->willReturn(null); // Cache is always empty

        // Use reflection to set the internal state for rate limiting
        $reflection = new \ReflectionClass($this->manager);
        $lastRequestTimeProp = $reflection->getProperty('last_request_time');
        $lastRequestTimeProp->setAccessible(true);
        $lastRequestTimeProp->setValue($this->manager, ['rate_limited_key' => time()]);

        $promise = $this->manager->getOrSet('rate_limited_key', fn() => $this->fail('Callback should not be called.'));

        $this->assertInstanceOf(RejectedPromise::class, $promise);

        $rejectionReason = '';
        $promise->then(null, function($reason) use (&$rejectionReason) {
            $rejectionReason = $reason;
        })->wait();

        $this->assertEquals('Rate limit exceeded for key: rate_limited_key', $rejectionReason);
    }
}