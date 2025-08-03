<?php

namespace Impulse\Core\Tests\Foundation;

use Impulse\Core\Kernel\Impulse;
use Impulse\Core\Tests\Foundation\Fixtures\SimpleProvider;
use PHPUnit\Framework\TestCase;

class ProviderManagerTest extends TestCase
{
    public function testProviderRegistrationAndBoot(): void
    {
        Impulse::boot();

        $provider = new SimpleProvider();
        Impulse::registerProvider($provider);
        Impulse::bootProviders();

        $this->assertTrue($provider->registered);
        $this->assertTrue($provider->booted);
    }
}
