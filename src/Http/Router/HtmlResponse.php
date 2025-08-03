<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

use Impulse\Core\Support\Collector\HeadCollector;
use Impulse\Core\Support\Collector\ScriptCollector;
use Impulse\Core\Support\Collector\StyleCollector;
use Impulse\Core\App;
use Impulse\Translation\Contract\TranslatorInterface;
use ScssPhp\ScssPhp\Exception\SassException;
use Impulse\Core\Support\Profiler;

final class HtmlResponse
{
    /**
     * @throws SassException|\ReflectionException
     * @throws \Exception
     */
    public function render(object $meta, string $bodyContent): string
    {
        Profiler::start('html:render');
        $translator = App::get(TranslatorInterface::class);
        $head = $this->renderHead($meta);
        $locale = $translator->getLocale();

        $template = <<<HTML
            <!DOCTYPE html>
            <html lang="{$locale}">
                <head>
                    {$head}
                </head>
                <body>
                    {$bodyContent}
                </body>
            </html>
        HTML;

        $html = $this->minify($template);
        Profiler::stop('html:render');

        return $html;
    }

    /**
     * @throws SassException
     * @throws \ReflectionException
     */
    public function emit(object $meta, string $bodyContent): void
    {
        Profiler::start('html:emit');
        ob_start();
        echo $this->render($meta, $bodyContent);
        ob_end_flush();
        Profiler::stop('html:emit');
    }

    /**
     * @throws \Exception
     */
    public function renderHead(object $meta): string
    {
        Profiler::start('html:head');
        $title = $meta->title
            ? htmlspecialchars($meta->title, ENT_QUOTES, 'UTF-8')
            : 'ImpulsePHP';

        HeadCollector::add('<meta charset="utf-8">', 100);
        HeadCollector::add("<title>{$title}</title>", 99);
        HeadCollector::add('<meta name="viewport" content="width=device-width, initial-scale=1">', 98);

        ScriptCollector::addFile('/impulse.js');

        // Render
        $head = HeadCollector::renderHead();
        $head .= StyleCollector::renderStyle();
        $head .= ScriptCollector::renderScript();

        HeadCollector::clear();
        StyleCollector::clear();
        ScriptCollector::clear();

        Profiler::stop('html:head');

        return $head;
    }

    private function minify(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        $html = preg_replace('/>\s+</', '><', $html);

        return trim($html);
    }
}
