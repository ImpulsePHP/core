<?php

namespace Impulse\Core\Renderer;

use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Impulse\Core\Support\Profiler;

#[Renderer(
    name: 'html'
)]
final class HtmlRenderer implements TemplateRendererInterface
{
    public function render(string $template, array $data = []): string
    {
        Profiler::start('view:' . $template);
        Profiler::stop('view:' . $template);
        Profiler::recordView('html', $template);

        return $template;
    }
}
