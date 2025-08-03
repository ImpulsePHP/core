<?php

declare(strict_types=1);

namespace Impulse\Core\Bootstrap;

use Impulse\Core\Component\State\State;
use Impulse\Core\Component\Store\Store;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Contracts\EventDispatcherInterface;
use Impulse\Core\Contracts\ExceptionHandlerInterface;
use Impulse\Core\Contracts\ServiceProviderInterface;
use Impulse\Core\Contracts\StateInterface;
use Impulse\Core\Contracts\StoreInterface;
use Impulse\Core\Event\EventDispatcher;
use Impulse\Core\Http\ExceptionHandler;

final class CoreServiceProvider implements ServiceProviderInterface
{
    public function register(ImpulseContainer $container): void
    {
        $container->set(EventDispatcherInterface::class, fn () => new EventDispatcher());
        $container->set(StateInterface::class, fn () => new State());
        $container->set(StoreInterface::class, fn () => new Store());
        $container->set(ExceptionHandlerInterface::class, fn () => new ExceptionHandler());
    }

    public function boot(ImpulseContainer $container): void
    {
        // Core services do not require additional boot logic for now.
    }
}
