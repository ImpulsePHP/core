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

        self::$enabled = Config::get('env', 'prod') === 'dev' && (bool) Config::get('devtools', false);
        self::$initialized = true;

        if (!self::$enabled) {
            return;
        }

        self::$emitter = $emitter ?? new SocketEmitter();
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
