<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\RateLimitError;
use PHPUnit\Framework\TestCase;

class RateLimitErrorTest extends TestCase
{
    public function testRateLimitErrorCanBeCreated(): void
    {
        $exception = new RateLimitError('Test message');
        $this->assertInstanceOf(RateLimitError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}