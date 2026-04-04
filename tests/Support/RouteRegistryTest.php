<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Support;

use Impulse\Core\Http\Router\RouteRegistry;
use PHPUnit\Framework\TestCase;

final class RouteRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        RouteRegistry::clear();
    }

    protected function tearDown(): void
    {
        RouteRegistry::clear();
    }

    public function testRegisterMergesRoutesAndStoresProviderRoutes(): void
    {
        RouteRegistry::register('ProviderA', ['/a' => 'route_a']);
        RouteRegistry::register('ProviderB', ['/b' => 'route_b']);

        $this->assertSame(['/a' => 'route_a', '/b' => 'route_b'], RouteRegistry::getAllRoutes());
        $this->assertSame(['/a' => 'route_a'], RouteRegistry::getProviderRoutes('ProviderA'));
        $this->assertSame([], RouteRegistry::getProviderRoutes('UnknownProvider'));
    }

    public function testClearResetsState(): void
    {
        RouteRegistry::register('ProviderA', ['/a' => 'route_a']);

        RouteRegistry::clear();

        $this->assertSame([], RouteRegistry::getAllRoutes());
        $this->assertSame([], RouteRegistry::getProviderRoutes('ProviderA'));
    }
}

