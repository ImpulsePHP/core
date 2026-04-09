<?php

declare(strict_types=1);

namespace Impulse\Core\Provider;

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\DevTools\DevToolsEmitterInterface;
use Impulse\Core\DevTools\EventCollector;
use Impulse\Core\DevTools\SocketEmitter;
use Impulse\Core\Support\Config;

final class DevToolsProvider extends AbstractProvider
{
    protected function registerServices(ImpulseContainer $container): void
    {
        $container->set(
            DevToolsEmitterInterface::class,
            static function (): DevToolsEmitterInterface {
                $address = Config::get('devtools.address');

                return new SocketEmitter(
                    is_string($address) && $address !== '' ? $address : null
                );
            }
        );
    }

    /**
     * @throws \Exception
     */
    protected function onBoot(ImpulseContainer $container): void
    {
        if ($container->has(DevToolsEmitterInterface::class)) {
            EventCollector::init($container->get(DevToolsEmitterInterface::class));
        } else {
            EventCollector::init();
        }

        $runtimeClass = 'Impulse\\DevTools\\Bridge\\DevToolsRuntime';
        if (class_exists($runtimeClass) && method_exists($runtimeClass, 'boot')) {
            $runtimeClass::boot();
        }
    }
}
