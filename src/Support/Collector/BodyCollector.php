<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collector;

final class BodyCollector
{
    /**
     * @var array<int, array{html: string, priority: int}>
     */
    private static array $elements = [];

    public static function add(string $html, int $priority = 0): void
    {
        if (trim($html) === '') {
            return;
        }

        self::$elements[] = [
            'html' => $html,
            'priority' => $priority,
        ];
    }

    public static function clear(): void
    {
        self::$elements = [];
    }

    public static function render(): string
    {
        $elements = self::$elements;
        usort($elements, static function (array $left, array $right): int {
            return $right['priority'] <=> $left['priority'];
        });

        return implode('', array_map(
            static fn(array $element): string => $element['html'],
            $elements
        ));
    }
}
