<?php
namespace Impulse\Core\Tests\Container\Fixtures;
class Bar {
    public Foo $foo;
    public function __construct(Foo $foo) { $this->foo = $foo; }
}
