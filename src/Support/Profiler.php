<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

use Impulse\Core\DevTools\Collectors\ProfilerCollector;

final class Profiler
{
    /**
     * @var array<string, float>
     */
    private static array $timers = [];

    /**
     * @var array<string, array{duration: float, memory: int}>
     */
    private static array $stats = [];

    /**
     * @var array<int, array{engine: string, template: string}>
     */
    private static array $views = [];

    /**
     * @throws \JsonException
     */
    private static function enabled(): bool
    {
        return Config::get('env', 'prod') === 'dev';
    }

    public static function start(string $name): void
    {
        if (!self::enabled()) {
            return;
        }

        self::$timers[$name] = microtime(true);
    }

    /**
     * @throws \JsonException
     */
    public static function stop(string $name): void
    {
        if (!isset(self::$timers[$name]) || !self::enabled()) {
            return;
        }

        $duration = microtime(true) - self::$timers[$name];
        unset(self::$timers[$name]);

        $memory = memory_get_usage(true);
        self::$stats[$name]['duration'] = $duration;
        self::$stats[$name]['memory'] = $memory;

        ProfilerCollector::record($name, $duration, $memory);
    }

    /**
     * @throws \JsonException
     */
    public static function clear(): void
    {
        if (!self::enabled()) {
            return;
        }

        self::$timers = [];
        self::$stats = [];
        self::$views = [];
    }

    /**
     * @throws \JsonException
     */
    public static function getStats(): array
    {
        if (!self::enabled()) {
            return [];
        }

        return self::$stats;
    }

    /**
     * @throws \JsonException
     */
    public static function recordView(string $engine, string $template): void
    {
        if (!self::enabled()) {
            return;
        }

        self::$views[] = [
            'engine' => $engine,
            'template' => $template,
        ];
    }

    /**
     * @throws \JsonException
     */
    public static function getViews(): array
    {
        if (!self::enabled()) {
            return [];
        }

        return self::$views;
    }

    /**
     * @throws \JsonException
     */
    public static function flush(): array
    {
        if (!self::enabled()) {
            return [];
        }

        $stats = self::$stats;
        self::$stats = [];
        self::$views = [];

        return $stats;
    }
}
