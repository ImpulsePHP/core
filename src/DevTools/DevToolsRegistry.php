<?php

declare(strict_types=1);

namespace Impulse\Core\DevTools;

final class DevToolsRegistry
{
    /** @var array<int, array<string, mixed>> */
    private static array $events = [];
    private static int $maxEvents = 1000;
    private static bool $collecting = false;

    public static function collect(string $type, array $data): void
    {
        if (self::$collecting) {
            return;
        }

        if (memory_get_usage() > 100 * 1024 * 1024) { // 100MB
            self::cleanup();
        }

        self::$collecting = true;

        try {
            $event = array_merge([
                'type' => $type,
                'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
            ], $data);

            if (count(self::$events) >= self::$maxEvents) {
                self::$events = array_slice(self::$events, -intval(self::$maxEvents * 0.8)); // Garder 80%
            }

            self::$events[] = $event;

            EventCollector::emit($event);

        } finally {
            self::$collecting = false;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$events;
    }

    public static function clear(): void
    {
        self::$events = [];
    }

    public static function setMaxEvents(int $maxEvents): void
    {
        self::$maxEvents = $maxEvents;
    }

    private static function cleanup(): void
    {
        self::$events = array_slice(self::$events, -100);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    public static function getMemoryStats(): array
    {
        return [
            'events_count' => count(self::$events),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'max_events' => self::$maxEvents,
        ];
    }
}
