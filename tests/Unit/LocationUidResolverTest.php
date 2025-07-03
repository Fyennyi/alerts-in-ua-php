<?php

namespace Tests\Unit;

use Fyennyi\AlertsInUa\Model\LocationUidResolver;
use PHPUnit\Framework\TestCase;

class LocationUidResolverTest extends TestCase
{
    public function testResolveUid()
    {
        $resolver = new LocationUidResolver();
        
        $this->assertEquals(31, $resolver->resolveUid('м. Київ'));
        $this->assertEquals(22, $resolver->resolveUid('Харківська область'));
        $this->assertEquals('Unknown UID', $resolver->resolveUid('Неіснуюча область'));
    }

    public function testResolveLocationTitle()
    {
        $resolver = new LocationUidResolver();
        
        $this->assertEquals('м. Київ', $resolver->resolveLocationTitle(31));
        $this->assertEquals('Харківська область', $resolver->resolveLocationTitle(22));
        $this->assertEquals('Unknown location', $resolver->resolveLocationTitle(999));
    }
}
