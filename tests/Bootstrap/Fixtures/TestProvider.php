<?php

namespace Impulse\Core\Tests\Bootstrap\Fixtures;

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Contracts\ServiceProviderInterface;

class TestProvider implements ServiceProviderInterface
{
    public bool $registered = false;
    public bool $booted = false;

    public function register(ImpulseContainer $container): void
    {
        $this->registered = true;
        $container->set('test.value', fn () => 42);
    }

    public function boot(ImpulseContainer $container): void
    {
        if ($container->has('test.value')) {
            $this->booted = true;
        }
    }
}
