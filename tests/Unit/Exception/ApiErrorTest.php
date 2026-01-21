<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\ApiError;
use PHPUnit\Framework\TestCase;

class ApiErrorTest extends TestCase
{
    public function testApiErrorCanBeCreated(): void
    {
        $exception = new ApiError('Test message');
        $this->assertInstanceOf(ApiError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
