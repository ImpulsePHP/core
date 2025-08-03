<?php

declare(strict_types=1);

namespace Impulse\Core\Provider;

use Impulse\Core\Contracts\HasComponentNamespacesInterface;
use Impulse\Core\Contracts\HasComponentRoutesInterface;
use Impulse\Core\Contracts\ServiceProviderInterface;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Http\Router\RouteLoader;
use Impulse\Core\Http\Router\RouteRegistry;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\DevError;

abstract class AbstractProvider implements ServiceProviderInterface
{
    public function register(ImpulseContainer $container): void
    {
        $this->registerServices($container);
    }

    /**
     * @throws \JsonException
     */
    public function boot(ImpulseContainer $container): void
    {
        if ($this instanceof HasComponentRoutesInterface) {
            $this->autoRegisterComponentRoutes();
        }

        if ($this instanceof HasComponentNamespacesInterface) {
            $this->autoRegisterComponentNamespaces();
        }

        $this->onBoot($container);
    }

    private function autoRegisterComponentRoutes(): void
    {
        $baseDir = $this->getComponentRoutes();
        if (empty($baseDir)) {
            return;
        }

        $baseDir = realpath($baseDir);
        if ($baseDir === false) {
            throw new \RuntimeException("Le répertoire {$baseDir} n'existe pas");
        }

        $routeLoader = new RouteLoader($baseDir);
        $routes = $routeLoader->load();

        RouteRegistry::register(self::class, $routes);
    }

    /**
     * @throws \JsonException
     */
    private function autoRegisterComponentNamespaces(): void
    {
        $namespaces = $this->getComponentNamespaces();
        if (empty($namespaces)) {
            return;
        }

        Config::load();
        $currentNamespaces = Config::get('component_namespaces', []);

        foreach ($namespaces as $namespace) {
            $namespace = rtrim($namespace, '\\') . '\\';
            if (!in_array($namespace, $currentNamespaces, true)) {
                $currentNamespaces[] = $namespace;
            }
        }

        Config::set('component_namespaces', $currentNamespaces);

        try {
            Config::save();
        } catch (\Exception $e) {
            DevError::respond("Erreur lors de la sauvegarde des namespaces : " . $e->getMessage());
        }
    }

    protected function onBoot(ImpulseContainer $container): void
    {
        // Par défaut, ne fait rien
    }

    protected function registerServices(ImpulseContainer $container): void
    {
        // Par défaut, ne fait rien
    }
}
