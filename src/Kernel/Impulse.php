<?php

declare(strict_types=1);

namespace Impulse\Core\Kernel;

use Impulse\Core\Contracts\ImpulseKernelInterface;
use Impulse\Core\Contracts\ProviderInterface;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Impulse\Core\Factory\OptimizedFactory;
use Impulse\Core\Foundation\ProviderManager;
use Impulse\Core\Http\Router\RouteRegistry;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\Profiler;

final class Impulse implements ImpulseKernelInterface
{
    private static ?TemplateRendererInterface $renderer = null;
    private static ?ProviderManager $providers = null;

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function boot(): void
    {
        Profiler::start('kernel:boot');
        Config::reset();
        Config::load();

        RouteRegistry::clear();
        OptimizedFactory::reset();

        $engine = Config::get('template_engine');
        $path = getcwd() . '/../' . ltrim(Config::get('template_path', 'views'), '/');

        self::$renderer = OptimizedFactory::create('renderer', [
            'engine' => $engine,
            'path' => $path,
        ]);

        self::$providers = new ProviderManager();
        Profiler::stop('kernel:boot');
    }

    public static function renderer(): ?TemplateRendererInterface
    {
        return self::$renderer;
    }

    /**
     * @throws \JsonException
     */
    public static function registerProvider(ProviderInterface $provider): void
    {
        if (!self::$providers) {
            self::$providers = new ProviderManager();
        }

        self::$providers->registerProvider($provider);
    }

    /**
     * @throws \JsonException
     */
    public static function bootProviders(): void
    {
        self::$providers?->bootProviders();
    }
}
