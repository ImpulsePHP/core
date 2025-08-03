<?php

declare(strict_types=1);

namespace Impulse\Core\DevTools\Collectors;

use Impulse\Core\DevTools\DevToolsRegistry;
use Impulse\Core\Support\Config;

final class LoggerCollector
{
    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $enabled = false;
        if (Config::isLoaded()) {
            $enabled = (bool) Config::get('logs.enabled', false);
        }

        if (!$enabled) {
            return;
        }

        $trace = debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        DevToolsRegistry::collect('log', [
            'context' => [
                'file' => $file,
                'line' => $line,
            ],
            'payload' => [
                'level' => strtolower($level),
                'message' => $message,
                'context' => $context,
            ],
        ]);

        $logDir = getcwd() . '/../var/logs';
        if (!is_dir($logDir) && !mkdir($logDir, 0755, true) && !is_dir($logDir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $logDir));
        }

        $logFile = $logDir . '/impulse.log';
        if (is_file($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
            @rename($logFile, $logDir . '/impulse.log.' . date('YmdHis'));
        }

        $contextStr = '';
        if ($context !== []) {
            $contextStr = ' context: ' . json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        }

        $lineStr = sprintf(
            "[%s] [%s] %s:%d %s%s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $file,
            $line,
            $message,
            $contextStr
        );

        @file_put_contents($logFile, $lineStr, FILE_APPEND);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('debug', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     * @throws \JsonException
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }
}
