<?php

declare(strict_types=1);

namespace Impulse\Core\Component\Transformer;

use Composer\Autoload\ClassLoader;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Exceptions\ComponentHtmlTransformerException;
use Impulse\Core\Factory\OptimizedFactory;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\Helper;

final class ComponentHtmlTransformer
{
    private static ?self $instance = null;
    private static ?array $componentTagsCache = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public function process(string $html): string
    {
        static $globalCounters = [];

        if (empty($html)) {
            return '';
        }

        $html = str_replace(["\r\n", "\r"], "\n", $html);
        if (str_starts_with($html, "\xEF\xBB\xBF")) {
            $html = substr($html, 3);
        }

        $maxIterations = 10;
        $iteration = 0;

        do {
            $iteration++;
            if ($iteration > $maxIterations) {
                throw new ComponentHtmlTransformerException(
                    "Trop d'itérations de composants détectées. Possible boucle infinie."
                );
            }

            $dom = $this->loadDom($html);
            $componentTags = $this->getComponentTags();
            $found = $this->replaceComponents($dom, $componentTags, $globalCounters);

            $html = $this->extractHtmlWithPreservation($dom);

        } while ($found && $iteration < $maxIterations);

        return trim($html);
    }

    private function loadDom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $prev = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><!DOCTYPE html><html lang="en"><body>' . $html . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        return $dom;
    }

    /**
     * @throws \ReflectionException
     */
    private function replaceComponents(\DOMDocument $dom, array $componentTags, array &$globalCounters): bool
    {
        $found = false;
        $xpath = new \DOMXPath($dom);

        foreach ($componentTags as $tagName => $className) {
            foreach ($xpath->query("//$tagName") as $node) {
                if (!$node instanceof \DOMElement) {
                    continue;
                }

                if ($this->isInsideCodeTag($node)) {
                    continue;
                }

                $props = [];
                foreach ($node->attributes as $attr) {
                    $propName = $this->kebabToCamelCase($attr->name);
                    $props[$propName] = $attr->value;
                }

                $componentBase = (new \ReflectionClass($className))->getShortName();
                $componentBase = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $componentBase));
                $globalCounters[$componentBase] = ($globalCounters[$componentBase] ?? 0) + 1;
                $id = $componentBase . '_imbrication_' . $globalCounters[$componentBase];

                $raw = '';
                foreach (iterator_to_array($node->childNodes) as $child) {
                    if ($child instanceof \DOMElement && $child->tagName === 'slot') {
                        continue;
                    }

                    if ($child->parentNode->isSameNode($node)) {
                        $raw .= $dom->saveHTML($child);
                    }
                }

                $props['__slot'] = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                $instance = OptimizedFactory::create('component', [
                    'class' => $className,
                    'defaults' => $props,
                    'id' => $id,
                ]);

                $rendered = trim($instance->render());

                if (Helper::isValidateJson($rendered)) {
                    throw new ComponentHtmlTransformerException(
                        sprintf(
                            "Le composant %s a retourné du JSON depuis render(). " .
                            "Cela indique probablement que render() a été appelé avec un paramètre de mise \xE0 jour. " .
                            "ComponentHtmlTransformer attend du HTML brut.",
                            $className
                        )
                    );
                }

                $tmpDom = new \DOMDocument();
                $prevTmp = libxml_use_internal_errors(true);
                $tmpDom->loadHTML('<?xml encoding="utf-8" ?><!DOCTYPE html><html lang="en"><body>' . $rendered . '</body></html>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                libxml_clear_errors();
                libxml_use_internal_errors($prevTmp);

                $body = $tmpDom->getElementsByTagName('body')->item(0);

                $firstRenderedNode = null;
                foreach ($body?->childNodes ?? [] as $child) {
                    if ($child instanceof \DOMElement) {
                        $firstRenderedNode = $child;
                        break;
                    }
                }

                if ($firstRenderedNode !== null && $firstRenderedNode->tagName === 'div') {
                    foreach ($node->attributes as $attr) {
                        if (
                            in_array($attr->name, ['id', 'class', 'style', 'title']) ||
                            str_starts_with($attr->name, 'data-') ||
                            str_starts_with($attr->name, 'aria-')
                        ) {
                            if ($attr->name === 'class') {
                                $existingClasses = $firstRenderedNode->getAttribute('class');
                                $existingArray = array_filter(explode(' ', $existingClasses));
                                $newArray = array_filter(explode(' ', $attr->value));
                                $mergedClasses = array_unique(array_merge($existingArray, $newArray));
                                $firstRenderedNode->setAttribute('class', implode(' ', $mergedClasses));
                            } else {
                                $firstRenderedNode->setAttribute($attr->name, $attr->value);
                            }
                        }
                    }
                }

                $importedFragment = $dom->createDocumentFragment();
                foreach (iterator_to_array($body?->childNodes) as $child) {
                    $imported = $dom->importNode($child, true);
                    $importedFragment->appendChild($imported);
                }

                $node->parentNode->replaceChild($importedFragment, $node);
                $found = true;
            }
        }

        return $found;
    }

    private function extractHtmlWithPreservation(\DOMDocument $dom): string
    {
        $body = $dom->getElementsByTagName('body')->item(0);
        $innerHTML = '';
        if ($body !== null) {
            $nodes = $body->childNodes;
            foreach ($nodes as $child) {
                $innerHTML .= $dom->saveHTML($child);
            }
        }

        return $this->preserveCodeContentAndDecode($innerHTML);
    }

    private function preserveCodeContentAndDecode(string $html): string
    {
        $codeContents = [];
        $placeholderCounter = 0;

        $html = preg_replace_callback(
            '/<code([^>]*)>(.*?)<\/code>/s',
            static function($matches) use (&$codeContents, &$placeholderCounter) {
                $placeholder = '___CODE_PLACEHOLDER_' . $placeholderCounter . '___';
                $codeContents[$placeholder] = [
                    'attributes' => $matches[1],
                    'content' => $matches[2]
                ];
                $placeholderCounter++;
                return $placeholder;
            },
            $html
        );

        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        foreach ($codeContents as $placeholder => $codeData) {
            $html = str_replace(
                $placeholder,
                '<code' . $codeData['attributes'] . '>' . $codeData['content'] . '</code>',
                $html
            );
        }

        return $html;
    }

    private function isInsideCodeTag(\DOMElement $node): bool
    {
        $parent = $node->parentNode;
        while ($parent !== null) {
            if ($parent instanceof \DOMElement && $parent->tagName === 'code') {
                return true;
            }

            $parent = $parent->parentNode;
        }

        return false;
    }

    /**
     * @throws \JsonException
     */
    private function getComponentTags(): array
    {
        if (self::$componentTagsCache !== null) {
            return self::$componentTagsCache;
        }

        $components = [];
        $namespaces = Config::get('component_namespaces', []);

        foreach ($namespaces as $namespace) {
            $namespace = rtrim($namespace, '\\') . '\\';

            $directory = $this->namespaceToPath($namespace);
            if (!$directory || !is_dir($directory)) {
                continue;
            }

            $phpFiles = iterator_to_array(new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            ));

            foreach ($phpFiles as $fileInfo) {
                if (!$fileInfo instanceof \SplFileInfo || $fileInfo->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = str_replace([$directory, '/', '.php'], ['', '\\', ''], $fileInfo->getPathname());
                $className = $namespace . $relativePath;

                try {
                    if (!class_exists($className)) {
                        continue;
                    }

                    $reflection = new \ReflectionClass($className);
                    if (!$reflection->isSubclassOf(ComponentInterface::class)) {
                        continue;
                    }

                    if (!$reflection->isInstantiable()) {
                        continue;
                    }

                    try {
                        $instance = $reflection->newInstanceWithoutConstructor();

                        if (method_exists($instance, 'getTagName')) {
                            $tagName = $instance->getTagName();
                        } elseif ($reflection->hasProperty('tagName') && $reflection->getProperty('tagName')->isPublic()) {
                            $tagName = $reflection->getProperty('tagName')->getValue($instance);
                        } else {
                            $tagName = null;
                        }

                        if (!is_string($tagName)) {
                            $tagName = null;
                        }

                        if ($tagName === null) {
                            $tagName = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $reflection->getShortName()));
                            $tagName = preg_replace('/-component$/i', '', strtolower($tagName));
                        }

                        $components[$tagName] = $className;
                    } catch (ComponentHtmlTransformerException|\ReflectionException $e) {
                        continue;
                    }
                } catch (ComponentHtmlTransformerException $e) {
                    continue;
                }
            }
        }

        return self::$componentTagsCache = $components;
    }

    private function namespaceToPath(string $namespace): ?string
    {
        static $loader = null;
        static $cache = [];

        if (isset($cache[$namespace])) {
            return $cache[$namespace];
        }

        if ($loader === null) {
            $autoloaderFiles = [
                getcwd() . '/vendor/autoload.php',
                getcwd() . '/../vendor/autoload.php',
                getcwd() . '/../../vendor/autoload.php',
                getcwd() . '/../../../vendor/autoload.php',
            ];

            foreach ($autoloaderFiles as $file) {
                if (file_exists($file)) {
                    $loader = require $file;
                    break;
                }
            }
        }

        if (!$loader instanceof ClassLoader) {
            return null;
        }

        $prefixesPsr4 = $loader->getPrefixesPsr4();
        foreach ($prefixesPsr4 as $prefix => $dirs) {
            if (str_starts_with($namespace, $prefix)) {
                $relative = str_replace('\\', '/', substr($namespace, strlen($prefix)));
                return $cache[$namespace] = rtrim($dirs[0], '/') . '/' . $relative;
            }
        }

        return $cache[$namespace] = null;
    }

    private function kebabToCamelCase(string $kebabCase): string
    {
        return lcfirst(str_replace('-', '', ucwords($kebabCase, '-')));
    }
}
