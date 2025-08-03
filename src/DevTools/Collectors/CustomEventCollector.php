<?php

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;

final class CustomEventCollector
{
    /**
     * @param string $type
     * @param array<string, mixed> $context
     * @param array<string, mixed> $payload
     */
    public static function record(string $type, array $context, array $payload): void
    {
        DevToolsRegistry::collect($type, [
            'context' => $context,
            'payload' => $payload,
        ]);
    }
}
