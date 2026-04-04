<?php

declare(strict_types=1);

namespace Impulse\Core\DevTools;

final class DevToolsRegistry
{
    private const MEMORY_CLEANUP_THRESHOLD_BYTES = 100 * 1024 * 1024;
    private const MEMORY_CLEANUP_KEEP_EVENTS = 100;
    private const TRIM_RATIO = 0.8;

    /** @var array<int, array<string, mixed>> */
    private static array $events = [];
    private static int $maxEvents = 1000;
    private static bool $collecting = false;

    public static function collect(string $type, array $data): void
    {
        if (self::$collecting) {
            return;
        }

        if (memory_get_usage() > self::MEMORY_CLEANUP_THRESHOLD_BYTES) {
            self::cleanup();
        }

        self::$collecting = true;

        try {
            $event = self::buildEvent($type, $data);
            self::trimEventsIfNeeded();

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
        self::resetEvents();
    }

    /** @internal Point d'ajustement réservé au tooling interne. */
    public static function setMaxEvents(int $maxEvents): void
    {
        self::$maxEvents = $maxEvents;
    }

    private static function cleanup(): void
    {
        self::$events = array_slice(self::$events, -self::MEMORY_CLEANUP_KEEP_EVENTS);
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function buildEvent(string $type, array $data): array
    {
        return array_merge([
            'type' => $type,
            'timestamp' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], $data);
    }

    private static function trimEventsIfNeeded(): void
    {
        if (count(self::$events) < self::$maxEvents) {
            return;
        }

        self::$events = array_slice(self::$events, -intval(self::$maxEvents * self::TRIM_RATIO));
    }

    private static function resetEvents(): void
    {
        self::$events = [];
    }

    /** @internal Statistiques de debug non garanties comme API publique stable. */
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
