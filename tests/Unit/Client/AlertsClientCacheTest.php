<?php

namespace Fyennyi\AlertsInUa\Tests\Unit\Client;

use Fyennyi\AlertsInUa\Client\AlertsClient;
use Fyennyi\AlertsInUa\Cache\SmartCacheManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AlertsClientCacheTest extends TestCase
{
    private AlertsClient $alertsClient;
    private SmartCacheManager $cacheManagerMock;

    protected function setUp() : void
    {
        $this->alertsClient = new AlertsClient('test-token');

        // Mock the SmartCacheManager
        $this->cacheManagerMock = $this->createMock(SmartCacheManager::class);

        // Use reflection to replace the actual cache manager with the mock
        $reflection = new ReflectionClass(AlertsClient::class);
        $cacheManagerProperty = $reflection->getProperty('cache_manager');
        $cacheManagerProperty->setAccessible(true);
        $cacheManagerProperty->setValue($this->alertsClient, $this->cacheManagerMock);
    }

    public function testConfigureCacheTtl()
    {
        $ttlConfig = ['active_alerts' => 300, 'default' => 60];

        // Expect the setTtl method to be called twice
        $this->cacheManagerMock->expects($this->exactly(2))
            ->method('setTtl');
        
        // Call the method to be tested
        $this->alertsClient->configureCacheTtl($ttlConfig);
    }

    public function testClearCacheWithPattern()
    {
        $pattern = 'some_pattern_*';

        // Expect the invalidatePattern method to be called with the specified pattern
        $this->cacheManagerMock->expects($this->once())
            ->method('invalidatePattern')
            ->with($pattern);

        // Call the method to be tested
        $this->alertsClient->clearCache($pattern);
    }

    public function testClearCacheWithNull()
    {
        // Expect the invalidatePattern method to be called with a wildcard '*'
        $this->cacheManagerMock->expects($this->once())
            ->method('invalidatePattern')
            ->with('*');

        // Call the method to be tested without a pattern (null)
        $this->alertsClient->clearCache();
    }
}
