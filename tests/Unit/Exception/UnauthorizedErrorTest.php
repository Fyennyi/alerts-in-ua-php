<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\UnauthorizedError;
use PHPUnit\Framework\TestCase;

class UnauthorizedErrorTest extends TestCase
{
    public function testUnauthorizedErrorCanBeCreated() : void
    {
        $exception = new UnauthorizedError('Test message');
        $this->assertInstanceOf(UnauthorizedError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
