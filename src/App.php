<?php

declare(strict_types=1);

namespace Impulse\Core;

use Impulse\Core\Bootstrap\CoreServiceProvider;
use Impulse\Core\Bootstrap\Kernel;
use Impulse\Core\Contracts\ImpulseKernelInterface;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\Logger;
use Impulse\Core\Kernel\Impulse;

final class App
{
    private static ?ImpulseKernelInterface $kernel = null;

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function boot(): void
    {
        Logger::info('Booting application', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        Impulse::boot();

        $providers = [];
        foreach (Config::get('providers', []) as $providerClass) {
            if (class_exists($providerClass)) {
                Logger::debug(
                    sprintf('Registering provider %s', $providerClass),
                    [
                        'class' => self::class,
                        'method' => __METHOD__,
                        'provider' => $providerClass,
                    ]
                );

                $providers[] = new $providerClass();
            }
        }

        Logger::debug('Registering CoreServiceProvider', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);
        $providers[] = new CoreServiceProvider();

        self::$kernel = new Kernel($providers);
        Logger::info(
            sprintf('Kernel initialized with %d providers', count($providers)),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'providers' => count($providers),
            ]
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function kernel(): ImpulseKernelInterface
    {
        if (!self::$kernel) {
            Logger::debug('Kernel not booted, bootstrapping', [
                'class' => self::class,
                'method' => __METHOD__,
            ]);
            self::boot();
        }

        Logger::debug('Retrieving kernel instance', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        return self::$kernel;
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function container(): ImpulseContainer
    {
        Logger::debug('Retrieving application container', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        return self::kernel()->getContainer();
    }

    /**
     * @throws \ReflectionException
     * @throws \Exception
     */
    public static function get(string $id): mixed
    {
        Logger::debug(
            sprintf('Fetching service %s from container', $id),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'service' => $id,
            ]
        );

        return self::container()->get($id);
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function has(string $id): bool
    {
        $result = self::container()->has($id);
        Logger::debug(
            sprintf('Checking if service %s exists: %s', $id, $result ? 'true' : 'false'),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'service' => $id,
            ]
        );

        return $result;
    }
}
