<?php

namespace Tests\Unit\Exception;

use Fyennyi\AlertsInUa\Exception\BadRequestError;
use PHPUnit\Framework\TestCase;

class BadRequestErrorTest extends TestCase
{
    public function testBadRequestErrorCanBeCreated(): void
    {
        $exception = new BadRequestError('Test message');
        $this->assertInstanceOf(BadRequestError::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }
}