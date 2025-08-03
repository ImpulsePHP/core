<?php

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;

final class ExceptionCollector
{
    public static function record(\Throwable $e): void
    {
        DevToolsRegistry::collect('exception', [
            'context' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ],
            'payload' => [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ],
        ]);
    }
}
