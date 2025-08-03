<?php

declare(strict_types=1);

namespace Impulse\Core\Component\Store;

final class Store
{
    /**
     * @var array<string, LocalStorageStoreInstance>
     */
    private static array $instances = [];
    private static int $maxInstances = 50;

    /**
     * @throws \JsonException
     */
    public static function get(string $name): LocalStorageStoreInstance
    {
        if (count(self::$instances) > self::$maxInstances) {
            self::$instances = array_slice(self::$instances, -30, null, true);
        }

        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = LocalStorageStoreInstance::createFromGlobals($name);
        }

        return self::$instances[$name];
    }

    public static function clearInstance(string $name): void
    {
        unset(self::$instances[$name]);
    }

    public static function clearAll(): void
    {
        self::$instances = [];
    }

    public static function getAllDataLocalStorage(): array
    {
        $localStorage = [];
        foreach(self::$instances as $instance) {
            $localStorage[$instance->getName()] = $instance->all();
        }

        return $localStorage;
    }
}
