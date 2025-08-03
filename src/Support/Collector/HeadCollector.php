<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collector;

final class HeadCollector
{
    private static array $elements = [];

    public static function add(string $element, int $priority = 0): void
    {
        self::$elements[] = [
            'element' => $element,
            'priority' => $priority,
        ];
    }

    /**
     * @return string[]
     */
    public static function getAll(): array
    {
        return self::$elements;
    }

    public static function clear(): void
    {
        self::$elements = [];
    }

    public static function renderHead(): string
    {
        $elements = self::$elements;
        usort($elements, static function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        $elements = array_map(static function ($element) {
            return $element['element'];
        }, $elements);

        return implode("\n", $elements);
    }
}
