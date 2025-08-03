<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

use Impulse\Core\Support\Config;
use Impulse\Core\Support\Profiler;

final class LayoutManager
{
    /**
     * @throws \JsonException
     */
    public function determine(object $page, object $meta): ?string
    {
        if (method_exists($page, 'layout') && $page->layout()) {
            return $page->layout();
        }

        if (isset($meta->layout) && $meta->layout) {
            return $meta->layout;
        }

        return Config::get('template_layout');
    }

    /**
     * @throws \ReflectionException
     */
    public function apply(string $layoutClass, string $bodyContent, string $route): string
    {
        Profiler::start('layout:apply:' . $layoutClass);

        if (!class_exists($layoutClass)) {
            throw new \RuntimeException("La classe de layout '{$layoutClass}' n'existe pas");
        }

        $classOnly = (new \ReflectionClass($layoutClass))->getShortName();
        $layoutKebabCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $classOnly));
        $layoutId = 'layout_' . str_replace('\\', '_', $layoutKebabCase);
        $defaults = $this->extractLayoutSlots($bodyContent);

        $props = ['__slot' => $defaults['__slot'] ?? ''];
        foreach ($defaults as $key => $value) {
            if ($key !== '__slot') {
                $props[$key] = $value;
            }
        }

        $layout = new $layoutClass($layoutId, $route, $props);

        foreach ($defaults as $key => $value) {
            if (str_starts_with($key, '__slot:')) {
                $slotName = substr($key, 7);
                if (method_exists($layout, 'setSlot')) {
                    $layout->setSlot($slotName, $value);
                }
            }
        }

        $html = $layout->render();
        Profiler::stop('layout:apply:' . $layoutClass);
        return $html;
    }

    /**
     * @return array<string, string>
     */
    private function extractLayoutSlots(string $html): array
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<!DOCTYPE html><meta charset="utf-8"><html><body>' . $html . '</body></html>', \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $slots = [];
        $defaultParts = [];

        foreach ($xpath->query('//slot-layout') as $node) {
            if (!$node instanceof \DOMElement) {
                continue;
            }

            $name = $node->getAttribute('name');
            $content = '';
            foreach (iterator_to_array($node->childNodes) as $child) {
                $content .= $dom->saveHTML($child);
            }

            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($name !== '') {
                $slots["__slot:$name"] = $content;
            } else {
                $defaultParts[] = $content;
            }

            $node->parentNode->removeChild($node);
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $remaining = '';
        if ($body !== null) {
            foreach (iterator_to_array($body->childNodes) as $child) {
                $remaining .= $dom->saveHTML($child);
            }
        }

        $remaining = html_entity_decode($remaining, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $slots['__slot'] = $remaining . implode('', $defaultParts);

        return $slots;
    }
}
