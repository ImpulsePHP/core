<?php

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;

final class ProfilerCollector
{
    public static function record(string $task, float $duration, int $memory): void
    {
        DevToolsRegistry::collect('profiler', [
            'context' => ['task' => $task],
            'payload' => [
                'duration' => $duration,
                'memory' => $memory,
            ],
        ]);
    }
}
