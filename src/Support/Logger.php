<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

use Impulse\Core\DevTools\Collectors\LoggerCollector;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        LoggerCollector::log($level, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function debug(string $message, array $context = []): void
    {
        LoggerCollector::debug($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function info(string $message, array $context = []): void
    {
        LoggerCollector::info($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function warning(string $message, array $context = []): void
    {
        LoggerCollector::warning($message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function error(string $message, array $context = []): void
    {
        LoggerCollector::error($message, $context);
    }
}
