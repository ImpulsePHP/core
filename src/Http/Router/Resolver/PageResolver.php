<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router\Resolver;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Http\Router\PageRouter;
use Impulse\DevTools\Exception\PageResolverException;

final class PageResolver
{
    /**
     * @throws PageResolverException
     */
    public static function resolvePage(string $uri): ?array
    {
        $router = new PageRouter();
        $pageProperty = $router->findComponentForRoute($uri);
        if (!$pageProperty) {
            return null;
        }

        $component = self::resolveByClass($pageProperty->class, $uri);
        if (!$component) {
            return null;
        }

        return self::getPageComponentStructure($component, $pageProperty);
    }

    /**
     * @throws PageResolverException
     */
    private static function resolveByClass(string $class, string $uri): ?ComponentInterface
    {
        if (!class_exists($class) || !is_subclass_of($class, ComponentInterface::class)) {
            throw new PageResolverException("La classe $class n'est pas un composant valide.");
        }

        $id = 'page_' . strtolower(str_replace('\\', '_', $class));

        return new $class($id, $uri);
    }

    private static function getPageComponentStructure(ComponentInterface $component, PageProperty $pageProperty): array
    {
        return [
            'id' => $component->getComponentId(),
            'class' => get_class($component),
            'states' => $component->getStates(),
            'watchers' => $component->getWatchers(),
            'methods' => $component->getMethods(),
            'properties' => $pageProperty->getData(),
        ];
    }
}
