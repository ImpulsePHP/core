<?php

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;

final class CacheCollector
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $payload
     */
    public static function record(array $context, array $payload): void
    {
        DevToolsRegistry::collect('cache', [
            'context' => $context,
            'payload' => $payload,
        ]);
    }
}
