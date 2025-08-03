<?php

namespace Impulse\Core\Tests\Foundation\Fixtures;

use Impulse\Core\Contracts\ProviderInterface;

class SimpleProvider implements ProviderInterface
{
    public bool $registered = false;
    public bool $booted = false;

    public function register(): void
    {
        $this->registered = true;
    }

    public function boot(): void
    {
        $this->booted = true;
    }
}
