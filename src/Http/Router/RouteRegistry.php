<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

final class RouteRegistry
{
    private static array $routes = [];
    private static array $providers = [];

    public static function register(string $providerClass, array $routes): void
    {
        self::$providers[$providerClass] = $routes;
        self::$routes = array_merge(self::$routes, $routes);
    }

    public static function getAllRoutes(): array
    {
        return self::$routes;
    }

    public static function getProviderRoutes(string $providerClass): array
    {
        return self::$providers[$providerClass] ?? [];
    }

    public static function clear(): void
    {
        self::$routes = [];
        self::$providers = [];
    }
}
