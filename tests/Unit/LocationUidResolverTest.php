<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Exception\InvalidParameterException;
use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use PHPUnit\Framework\TestCase;

class LocationUidResolverTest extends TestCase
{
    public function testResolveUid()
    {
        $resolver = new LocationUidResolver();

        $this->assertEquals(31, $resolver->resolveUid('м. Київ'));
        $this->assertEquals(22, $resolver->resolveUid('Харківська область'));

        // Test that InvalidParameterException is thrown for unknown location
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown location: Неіснуюча область');
        $resolver->resolveUid('Неіснуюча область');
    }

    public function testResolveLocationTitle()
    {
        $resolver = new LocationUidResolver();

        $this->assertEquals('м. Київ', $resolver->resolveLocationTitle(31));
        $this->assertEquals('Харківська область', $resolver->resolveLocationTitle(22));

        // Test that InvalidParameterException is thrown for unknown UID
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown UID: 999');
        $resolver->resolveLocationTitle(999);
    }

    public function testResolveUidWithUnknownLocationThrowsException()
    {
        $resolver = new LocationUidResolver();

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown location: Неіснуюча область');
        $resolver->resolveUid('Неіснуюча область');
    }

    public function testResolveLocationTitleWithUnknownUidThrowsException()
    {
        $resolver = new LocationUidResolver();

        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Unknown UID: 999');
        $resolver->resolveLocationTitle(999);
    }
}
