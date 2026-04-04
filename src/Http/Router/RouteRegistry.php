<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

final class RouteRegistry
{
    private static array $routes = [];
    private static array $providers = [];

    public static function register(string $providerClass, array $routes): void
    {
        self::storeProviderRoutes($providerClass, $routes);
        self::appendRoutes($routes);
    }

    public static function getAllRoutes(): array
    {
        return self::$routes;
    }

    /**
     * @internal Utilisé pour le debug/introspection interne des routes provider.
     */
    public static function getProviderRoutes(string $providerClass): array
    {
        return self::$providers[$providerClass] ?? [];
    }

    public static function clear(): void
    {
        self::resetState();
    }

    private static function storeProviderRoutes(string $providerClass, array $routes): void
    {
        self::$providers[$providerClass] = $routes;
    }

    private static function appendRoutes(array $routes): void
    {
        self::$routes = array_merge(self::$routes, $routes);
    }

    private static function resetState(): void
    {
        self::$routes = [];
        self::$providers = [];
    }
}
