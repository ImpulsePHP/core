<?php

declare(strict_types=1);

namespace Impulse\Core\Renderer;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Impulse\Core\Exceptions\BladeRendererException;
use Impulse\Core\Support\Profiler;

#[Renderer(
    name: 'blade',
    bundle: 'illuminate/view'
)]
final class BladeRenderer implements TemplateRendererInterface
{
    private Factory $factory;

    public function __construct(string $viewsPath = '')
    {
        $cachePath = getcwd() . '/../var/storage/cache/blade';
        if (!is_dir($cachePath) && !mkdir($cachePath, 0755, true) && !is_dir($cachePath)) {
            throw new BladeRendererException(sprintf('Directory "%s" was not created', $cachePath));
        }

        $filesystem = new Filesystem();
        $resolver = new EngineResolver();
        $compiler = new BladeCompiler($filesystem, $cachePath);
        $resolver->register('blade', fn () => new CompilerEngine($compiler));

        $container = new Container();
        $finder = new FileViewFinder($filesystem, [$viewsPath]);
        $this->factory = new Factory($resolver, $finder, new Dispatcher($container));
    }

    public function render(string $template, array $data = []): string
    {
        $template = str_replace(['.blade.php', '.php', '/'], '', $template);
        Profiler::start('view:' . $template);
        $html = $this->factory->make($template, $data)->render();
        Profiler::stop('view:' . $template);
        Profiler::recordView('blade', $template);

        return $html;
    }
}
