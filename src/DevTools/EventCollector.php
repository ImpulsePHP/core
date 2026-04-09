<?php

namespace Impulse\Core\DevTools;

use Impulse\Core\Support\Config;

final class EventCollector
{
    private static bool $initialized = false;
    private static ?DevToolsEmitterInterface $emitter = null;
    private static bool $enabled = false;

    /**
     * @throws \JsonException
     */
    public static function init(?DevToolsEmitterInterface $emitter = null): void
    {
        if (self::$initialized) {
            return;
        }

        self::$enabled = Config::get('env', 'prod') === 'dev' && self::isDevToolsEnabled();
        self::$initialized = true;

        if (!self::$enabled) {
            return;
        }

        self::$emitter = $emitter ?? new SocketEmitter();
    }

    /**
     * @throws \JsonException
     */
    private static function isDevToolsEnabled(): bool
    {
        $config = Config::get('devtools', false);

        if (is_array($config)) {
            return (bool) ($config['enabled'] ?? false);
        }

        return (bool) $config;
    }

    public static function emit(array $event): void
    {
        if (!self::$initialized) {
            self::init();
        }
        if (!self::$enabled || self::$emitter === null) {
            return;
        }

        try {
            self::$emitter->emit($event);
        } catch (\Throwable) {
            // ignore emitter errors
        }
    }
}
