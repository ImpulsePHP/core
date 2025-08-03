<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

final class ComponentNamespaceRegistry
{
    private static array $dynamicNamespaces = [];
    private static bool $initialized = false;

    /**
     * @throws \JsonException
     */
    public static function initialize(): void
    {
        if (self::$initialized) {
            return;
        }

        Config::load();
        self::$initialized = true;
    }

    /**
     * @throws \JsonException
     */
    public static function addDynamic(string $namespace): void
    {
        self::initialize();

        $namespace = rtrim($namespace, '\\') . '\\';

        if (!in_array($namespace, self::$dynamicNamespaces, true)) {
            self::$dynamicNamespaces[] = $namespace;
        }
    }

    /**
     * @throws \JsonException
     */
    public static function addMultipleDynamic(array $namespaces): void
    {
        foreach ($namespaces as $namespace) {
            self::addDynamic($namespace);
        }
    }

    /**
     * @throws \JsonException
     */
    public static function getAllNamespaces(): array
    {
        self::initialize();

        $configNamespaces = Config::get('component_namespaces', []);
        return array_unique(array_merge($configNamespaces, self::$dynamicNamespaces));
    }

    /**
     * @throws \JsonException
     */
    public static function getDynamicNamespaces(): array
    {
        self::initialize();
        return self::$dynamicNamespaces;
    }

    public static function clearDynamic(): void
    {
        self::$dynamicNamespaces = [];
    }
}
