<?php

declare(strict_types=1);

namespace Impulse\Core\Renderer;

use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use Impulse\Core\Support\Profiler;

#[Renderer(
    name: 'twig',
    bundle: 'twig/twig:^3.0'
)]
final class TwigRenderer implements TemplateRendererInterface
{
    private Environment $twig;

    public function __construct(string $viewsPath = '')
    {
        $loader = new FilesystemLoader($viewsPath);
        $this->twig = new Environment($loader, [
            'cache' => getcwd() . '/../var/storage/cache/twig',
            'auto_reload' => true,
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(string $template, array $data = []): string
    {
        $template = strtolower($template);
        if (!str_ends_with($template, '.twig')) {
            $template .= '.twig';
        }
        Profiler::start('view:' . $template);
        $html = $this->twig->render($template, $data);
        Profiler::stop('view:' . $template);
        Profiler::recordView('twig', $template);

        return $html;
    }
}
