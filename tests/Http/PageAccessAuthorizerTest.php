<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Http;

use Impulse\Core\App;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Router\PageAccessAuthorizer;
use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

final class PageAccessAuthorizerTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        $this->resetAppKernel();
        App::boot();
    }

    protected function tearDown(): void
    {
        $this->resetAppKernel();
        Config::reset();
    }

    /**
     * @throws \ReflectionException
     */
    public function testIgnoresPagesWithoutRoles(): void
    {
        $authorizer = new PageAccessAuthorizer();

        $response = $authorizer->authorize(
            new Request('/admin'),
            new PageProperty(route: '/admin', class: 'App\\Page\\AdminPage')
        );

        $this->assertNull($response);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testReturnsNullWhenAclServiceIsMissing(): void
    {
        $authorizer = new PageAccessAuthorizer();

        $response = $authorizer->authorize(
            new Request('/admin'),
            new PageProperty(route: '/admin', roles: ['admin'], class: 'App\\Page\\AdminPage')
        );

        $this->assertNull($response);
    }

    /**
     * @throws \ReflectionException
     */
    public function testReturns403HtmlWhenRolesDoNotMatch(): void
    {
        $container = App::container();
        $container->set('Impulse\\Acl\\Contracts\\AclInterface', static fn () => new class {
            public function hasAnyRole(array $roles): bool
            {
                return false;
            }
        });
        $container->set('Impulse\\Acl\\Support\\AclConfig', static fn () => new class {
            public function forbiddenMessage(): string
            {
                return 'Zone interdite';
            }

            public function flashKey(): string
            {
                return 'acl_error';
            }
        });

        $authorizer = new PageAccessAuthorizer();

        $response = $authorizer->authorize(
            new Request('/admin'),
            new PageProperty(route: '/admin', roles: ['admin'], class: 'App\\Page\\AdminPage')
        );

        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Zone interdite', $response->getContent());
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testReturns403JsonWhenRequestExpectsJson(): void
    {
        $container = App::container();
        $container->set('Impulse\\Acl\\Contracts\\AclInterface', static fn () => new class {
            public function hasAnyRole(array $roles): bool
            {
                return false;
            }
        });

        $authorizer = new PageAccessAuthorizer();

        $response = $authorizer->authorize(
            new Request('/admin', 'GET', [], [], [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ]),
            new PageProperty(route: '/admin', roles: ['admin'], class: 'App\\Page\\AdminPage')
        );

        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type'] ?? null);
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function testReturnsNullWhenRolesMatch(): void
    {
        $container = App::container();
        $container->set('Impulse\\Acl\\Contracts\\AclInterface', static fn () => new class {
            public function hasAnyRole(array $roles): bool
            {
                return true;
            }
        });

        $authorizer = new PageAccessAuthorizer();

        $response = $authorizer->authorize(
            new Request('/admin'),
            new PageProperty(route: '/admin', roles: ['admin'], class: 'App\\Page\\AdminPage')
        );

        $this->assertNull($response);
    }

    private function resetAppKernel(): void
    {
        $reflection = new \ReflectionClass(App::class);
        $property = $reflection->getProperty('kernel');
        $property->setValue(null, null);
    }
}
