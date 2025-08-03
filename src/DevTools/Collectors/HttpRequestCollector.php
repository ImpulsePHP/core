<?php

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;

final class HttpRequestCollector
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $payload
     */
    public static function record(array $context, array $payload): void
    {
        DevToolsRegistry::collect('request', [
            'context' => $context,
            'payload' => $payload,
        ]);
    }
}
