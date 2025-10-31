<?php

namespace Tests\Unit\Client;

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

    public function testClearCacheWithTags()
    {
        $tags = ['active_alerts', 'history'];

        // Expect the invalidateTags method to be called with the specified tags
        $this->cacheManagerMock->expects($this->once())
            ->method('invalidateTags')
            ->with($tags);

        // Call the method to be tested
        $this->alertsClient->clearCache($tags);
    }
}
