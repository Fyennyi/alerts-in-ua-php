<?php

namespace Tests\Unit\Util;

use Fyennyi\AlertsInUa\Util\UserAgent;
use PHPUnit\Framework\TestCase;

class UserAgentTest extends TestCase
{
    private $backupEnv;

    protected function setUp() : void
    {
        parent::setUp();
        $this->backupEnv = getenv('AIU_USER_AGENT');
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        // Restore original environment variable
        if (false === $this->backupEnv) {
            putenv('AIU_USER_AGENT');
        } else {
            putenv('AIU_USER_AGENT=' . $this->backupEnv);
        }
    }

    public function testGetUserAgentWithEnvVar()
    {
        $customUserAgent = 'MyCustomUserAgent/1.0';
        putenv('AIU_USER_AGENT=' . $customUserAgent);

        $this->assertEquals($customUserAgent, UserAgent::getUserAgent());
    }

    public function testGetUserAgentWithDefault()
    {
        // Ensure the environment variable is not set
        putenv('AIU_USER_AGENT');

        $defaultAgent = 'alerts-in-ua-php/0.2.8 (+https://github.com/Fyennyi/alerts-in-ua-php)';
        $this->assertEquals($defaultAgent, UserAgent::getUserAgent());
    }
}
