<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\ForbiddenError;
use PHPUnit\Framework\TestCase;

class ForbiddenErrorTest extends TestCase
{
    public function testForbiddenErrorCanBeCreated(): void
    {
        $exception = new ForbiddenError('Test message');
        $this->assertInstanceOf(ForbiddenError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
