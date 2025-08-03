<?php

namespace Impulse\Core\Tests\Container;

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Tests\Container\Fixtures\Bar;
use Impulse\Core\Tests\Container\Fixtures\Baz;
use Impulse\Core\Tests\Container\Fixtures\Foo;
use PHPUnit\Framework\TestCase;

class ImpulseContainerTest extends TestCase
{
    public function testMakeAutoWiresDependencies(): void
    {
        $container = new ImpulseContainer();
        $container->registerNamespace('Impulse\\Core\\Tests\\Container\\Fixtures', __DIR__ . '/Fixtures');

        $bar = $container->make(Bar::class);
        $this->assertInstanceOf(Foo::class, $bar->foo);
    }

    public function testCallInjectsMethodDependencies(): void
    {
        $container = new ImpulseContainer();
        $container->registerNamespace('Impulse\\Core\\Tests\\Container\\Fixtures', __DIR__ . '/Fixtures');

        $baz = new Baz();
        $result = $container->call([$baz, 'combine']);
        $this->assertInstanceOf(Foo::class, $result[0]);
        $this->assertInstanceOf(Bar::class, $result[1]);
    }
}
