<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collector;

use Impulse\Core\Exceptions\ScriptCollectorException;

final class ScriptCollector
{
    private static array $scripts = [
        'files' => [],
        'code' => []
    ];

    public static function clear(): void
    {
        self::$scripts = [
            'files' => [],
            'code' => []
        ];
    }

    public static function addFile(string $path, bool $defer = false): void
    {
        if (empty(trim($path))) {
            throw new ScriptCollectorException("Le chemin du script ne peut pas être vide");
        }

        if (!isset(self::$scripts['files'][$path])) {
            self::$scripts['files'][$path] = $defer;
        }
    }

    public static function addCode(string $code): void
    {
        if (!empty(trim($code))) {
            self::$scripts['code'][] = $code;
        }
    }

    public static function getFiles(): array
    {
        return self::$scripts['files'] ?? [];
    }

    public static function getCode(): array
    {
        return self::$scripts['code'] ?? [];
    }

    public static function renderScript(): ?string
    {
        $scripts = '';
        $files = self::getFiles();

        if (!empty($files)) {
            foreach ($files as $filePath => $defer) {
                if (empty(trim($filePath))) {
                    continue;
                }

                $deferAttr = $defer ? ' defer' : '';
                $scripts .= "<script src=\"{$filePath}\"{$deferAttr}></script>";
            }
        }

        $codes = self::getCode();
        if (!empty($codes)) {
            $scripts .= '<script>' . implode('', $codes) . '</script>';
        }

        return !empty($scripts) ? $scripts : null;
    }
}
