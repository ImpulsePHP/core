<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

final class Flash
{
    private const SESSION_KEY = '_impulse_flash';

    public static function put(string $key, mixed $value): void
    {
        self::startSession();
        $_SESSION[self::SESSION_KEY][$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::startSession();

        return array_key_exists($key, $_SESSION[self::SESSION_KEY] ?? []);
    }

    public static function get(string $key, mixed $default = null, bool $remove = true): mixed
    {
        self::startSession();

        if (!array_key_exists($key, $_SESSION[self::SESSION_KEY] ?? [])) {
            return $default;
        }

        $value = $_SESSION[self::SESSION_KEY][$key];

        if ($remove) {
            unset($_SESSION[self::SESSION_KEY][$key]);
            self::cleanup();
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(bool $remove = true): array
    {
        self::startSession();

        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        if ($remove) {
            unset($_SESSION[self::SESSION_KEY]);
        }

        return $messages;
    }

    private static function cleanup(): void
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
