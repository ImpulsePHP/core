<?php

declare(strict_types=1);

namespace Impulse\Core\Bootstrap;

use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Contracts\ImpulseKernelInterface;
use Impulse\Core\Contracts\ServiceProviderInterface;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Impulse\Core\Support\Logger;

final class Kernel implements ImpulseKernelInterface
{
    private ImpulseContainer $container;
    /**
     * @var ServiceProviderInterface[]
     */
    private array $providers = [];

    /**
     * @param ServiceProviderInterface[] $providers
     * @throws \JsonException|\ReflectionException
     */
    public function __construct(array $providers = [], ?ImpulseContainer $container = null)
    {
        Logger::info('Initializing kernel', [
            'class' => self::class,
            'method' => __METHOD__,
            'providers' => count($providers),
        ]);

        $this->container = $container ?? new ImpulseContainer();
        $this->container->set(ImpulseContainer::class, fn() => $this->container);

        Logger::debug('Registering core namespace', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        $this->container->registerNamespace('Impulse\\Core', dirname(__DIR__));

        foreach ($providers as $provider) {
            Logger::debug(
                sprintf('Registering provider %s', $provider::class),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'provider' => $provider::class,
                ]
            );

            $this->providers[] = $provider;
            $this->container->call([$provider, 'register']);
        }

        foreach ($this->providers as $provider) {
            Logger::debug(
                sprintf('Booting provider %s', $provider::class),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'provider' => $provider::class,
                ]
            );
            $this->container->call([$provider, 'boot']);
        }
        Logger::info('Kernel ready', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

    }

    public function getContainer(): ImpulseContainer
    {
        return $this->container;
    }

    public static function boot(): void
    {
        // ...
    }

    public static function renderer(): ?TemplateRendererInterface
    {
        return null;
    }
}
