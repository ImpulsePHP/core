<?php

declare(strict_types=1);

namespace Impulse\Core\Foundation;

use Impulse\Core\Contracts\ProviderInterface;
use Impulse\Core\Support\Profiler;
use Impulse\Core\Support\Logger;

final class ProviderManager
{
    /**
     * @var ProviderInterface[]
     */
    private array $providers = [];

    /**
     * @throws \JsonException
     */
    public function registerProvider(ProviderInterface $provider): void
    {
        Profiler::start('provider:register:' . $provider::class);
        Logger::debug(
            sprintf('Registering provider %s', $provider::class),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'provider' => $provider::class,
            ]
        );

        $this->providers[] = $provider;
        $provider->register();
        Profiler::stop('provider:register:' . $provider::class);
    }

    /**
     * @throws \JsonException
     */
    public function bootProviders(): void
    {
        foreach ($this->providers as $provider) {
            Profiler::start('provider:boot:' . $provider::class);
            Logger::debug(
                sprintf('Booting provider %s', $provider::class),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'provider' => $provider::class,
                ]
            );

            $provider->boot();
            Profiler::stop('provider:boot:' . $provider::class);
        }
    }
}
