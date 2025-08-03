<?php

namespace Impulse\Core\Tests\Bootstrap;

use Impulse\Core\Bootstrap\Kernel;
use Impulse\Core\Tests\Bootstrap\Fixtures\TestProvider;
use PHPUnit\Framework\TestCase;

class KernelProviderTest extends TestCase
{
    public function testProvidersAreRegisteredAndBooted(): void
    {
        $provider = new TestProvider();
        $kernel = new Kernel([$provider]);

        $this->assertTrue($provider->registered);
        $this->assertTrue($provider->booted);
        $this->assertSame(42, $kernel->getContainer()->get('test.value'));
    }
}
