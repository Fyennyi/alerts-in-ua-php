<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\InternalServerError;
use PHPUnit\Framework\TestCase;

class InternalServerErrorTest extends TestCase
{
    public function testInternalServerErrorCanBeCreated() : void
    {
        $exception = new InternalServerError('Test message');
        $this->assertInstanceOf(InternalServerError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}
