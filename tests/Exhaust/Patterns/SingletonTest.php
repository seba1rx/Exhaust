<?php

declare(strict_types=1);

namespace tests\Exhaust\Patterns;

use Exhaust\Patterns\Singleton;
use PHPUnit\Framework\TestCase;

// Concrete subclass for testing
class ConcreteSingleton extends Singleton {}
class AnotherSingleton extends Singleton {}

final class SingletonTest extends TestCase
{
    public function test_getInstance_returns_instance_of_subclass(): void
    {
        $instance = ConcreteSingleton::getInstance();
        $this->assertInstanceOf(ConcreteSingleton::class, $instance);
    }

    public function test_getInstance_returns_same_instance(): void
    {
        $a = ConcreteSingleton::getInstance();
        $b = ConcreteSingleton::getInstance();
        $this->assertSame($a, $b);
    }

    public function test_different_subclasses_have_independent_instances(): void
    {
        $a = ConcreteSingleton::getInstance();
        $b = AnotherSingleton::getInstance();
        $this->assertNotSame($a, $b);
        $this->assertInstanceOf(ConcreteSingleton::class, $a);
        $this->assertInstanceOf(AnotherSingleton::class, $b);
    }

    public function test_wakeup_throws_exception(): void
    {
        $instance = ConcreteSingleton::getInstance();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot unserialize singleton');
        $instance->__wakeup();
    }

    public function test_clone_is_prevented(): void
    {
        // __clone is protected — cannot be called from outside.
        // Verify via ReflectionMethod that it is protected.
        $ref = new \ReflectionClass(Singleton::class);
        $clone = $ref->getMethod('__clone');
        $this->assertTrue($clone->isProtected(), '__clone must be protected');
        $this->assertTrue($clone->isFinal(), '__clone must be final');
    }

    public function test_constructor_is_protected(): void
    {
        $ref = new \ReflectionClass(Singleton::class);
        $ctor = $ref->getMethod('__construct');
        $this->assertTrue($ctor->isProtected(), '__construct must be protected');
        $this->assertTrue($ctor->isFinal(), '__construct must be final');
    }
}
