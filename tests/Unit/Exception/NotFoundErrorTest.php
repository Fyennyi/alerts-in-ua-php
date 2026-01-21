<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\NotFoundError;
use PHPUnit\Framework\TestCase;

class NotFoundErrorTest extends TestCase
{
    public function testNotFoundErrorCanBeCreated(): void
    {
        $exception = new NotFoundError('Test message');
        $this->assertInstanceOf(NotFoundError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
