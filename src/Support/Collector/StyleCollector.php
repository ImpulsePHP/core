<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collector;

use Impulse\Core\Support\Config;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\Exception\SassException;
use Impulse\Core\Support\DevError;

final class StyleCollector
{
    /**
     * @var array<string, array<int, string>>
     */
    private static array $styles = [];

    /**
     * @var array<string>
     */
    private static array $addedHashes = [];

    /**
     * @var array<string, string>
     */
    private static array $compiledCssCache = [];

    public static function clear(): void
    {
        self::$styles = [];
        self::$addedHashes = [];
        self::$compiledCssCache = [];
    }

    /**
     * @throws \JsonException
     */
    public static function addSheet(string $stylesheet, bool $insertContent = false): void
    {
        if ($insertContent && file_exists($stylesheet)) {
            $cssContent = file_get_contents($stylesheet);
            self::addCss($cssContent);
            return;
        }

        $hash = md5($stylesheet);
        if (!in_array($hash, self::$addedHashes)) {
            self::$styles['sheet'][] = $stylesheet;
            self::$addedHashes[] = $hash;
        }
    }

    /**
     * @throws \JsonException
     */
    public static function addProviderSheet(string $stylesheet, string $basePath, bool $insertContent = false): void
    {
        $fullPath = rtrim($basePath, '/') . '/' . ltrim($stylesheet, '/');

        if ($insertContent && file_exists($fullPath)) {
            $cssContent = file_get_contents($fullPath);
            self::addCss($cssContent);
            return;
        }

        $hash = md5($fullPath);
        if (!in_array($hash, self::$addedHashes)) {
            self::$styles['sheet'][] = $stylesheet;
            self::$addedHashes[] = $hash;
        }
    }

    /**
     * @throws \JsonException
     */
    public static function addCss(string $css): void
    {
        $hash = md5($css);
        if (!in_array($hash, self::$addedHashes, true)) {
            if (!isset(self::$compiledCssCache[$hash])) {
                try {
                    self::$compiledCssCache[$hash] = (new Compiler())->compileString($css)->getCss();
                } catch (SassException $e) {
                    DevError::respond('Erreur de compilation SCSS : ' . $e->getMessage());
                }
            }

            self::$styles['css'][] = $hash;
            self::$addedHashes[] = $hash;
        }
    }

    /**
     * @return array<int, string>
     */
    public static function getSheets(): array
    {
        return self::$styles['sheet'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public static function getCss(): array
    {
        return self::$styles['css'] ?? [];
    }

    /**
     * @throws \JsonException
     */
    public static function renderStyle(): ?string
    {
        static $storyCssProcessed = false;
        if (!$storyCssProcessed) {
            self::processStoryCss();
            $storyCssProcessed = true;
        }

        $styles = '';

        $styleSheets = self::getSheets();
        if (!empty($styleSheets)) {
            foreach ($styleSheets as $styleSheet) {
                $styles .= <<<HTML
                    <link rel="stylesheet" href="{$styleSheet}">
                HTML;
            }
        }

        $codesCss = self::getCss();
        if (!empty($codesCss)) {
            $styles .= '<style id="impulse-dynamic-styles">';
            foreach ($codesCss as $hash) {
                if (isset(self::$compiledCssCache[$hash])) {
                    $styles .= self::$compiledCssCache[$hash];
                }
            }

            $styles .= '</style>';
        }

        return $styles !== '' ? $styles : null;
    }

    /**
     * @throws \JsonException
     */
    private static function processStoryCss(): void
    {
        $story_css = Config::get('css', []);
        if (empty($story_css)) {
            return;
        }

        foreach ($story_css as $css) {
            if (is_string($css)) {
                self::addSheet($css, true);
            } elseif (is_array($css)) {
                $path = $css['path'];
                $basePath = $css['base'] ?? getcwd() . '/public';
                $inline = $css['inline'] ?? false;

                self::addProviderSheet($path, $basePath, $inline);
            }
        }
    }
}
