<?php
namespace Impulse\Core\Tests\Container\Fixtures;
class Baz {
    public function combine(Foo $foo, Bar $bar): array { return [$foo, $bar]; }
}
