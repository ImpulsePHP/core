<?php

declare(strict_types=1);

namespace Impulse\Core\Factory;

use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Contracts\TemplateRendererInterface;
use Impulse\Core\Exceptions\ImpulseException;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\Profiler;
use InvalidArgumentException;

final class OptimizedFactory
{
    /**
     * @var array<string, object>
     */
    private static array $instances = [];

    /**
     * @param non-empty-string $type
     * @param array<string, mixed> $config
     * @throws \ReflectionException
     */
    public static function create(string $type, array $config = []): object
    {
        Profiler::start('factory:create:' . $type);
        $key = $type . ':' . md5(serialize($config));

        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = match ($type) {
                'renderer' => self::createRenderer($config),
                'component' => self::createComponent($config),
                default => throw new InvalidArgumentException("Type '{$type}' non supporté par la factory")
            };
        }

        $instance = self::$instances[$key];
        Profiler::stop('factory:create:' . $type);
        return $instance;
    }

    /**
     * @throws \JsonException
     */
    private static function createRenderer(array $config): object
    {
        Profiler::start('factory:renderer');
        $engine = trim((string)($config['engine'] ?? Config::get('template_engine', ''))) ?: 'html';
        $path = $config['path'] ?? self::resolveTemplatePath();

        $baseNamespace = 'Impulse\\Core\\Renderer';
        $directories = [
            self::getProjectRoot() . '/src/Renderer',
            __DIR__ . '/../Renderer'
        ];

        $namespaceMap = [
            $directories[0] => 'App\\Renderer',
            $directories[1] => $baseNamespace
        ];

        if (defined('IMPULSE_RENDERER_NAMESPACE_MAP')) {
            /** @var array<string, string> $customNamespaces */
            $customNamespaces = constant('IMPULSE_RENDERER_NAMESPACE_MAP');
            foreach ($customNamespaces as $ns => $dir) {
                $directories[] = $dir;
                $namespaceMap[$dir] = rtrim($ns, '\\') . '\\';
            }
        }

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || !str_ends_with($file->getFilename(), 'Renderer.php')) {
                    continue;
                }

                $namespace = $namespaceMap[$directory] ?? $baseNamespace;
                $className = $namespace . $file->getBasename('.php');
                require_once $file->getPathname();

                if (!class_exists($className)) {
                    continue;
                }

                $reflection = new \ReflectionClass($className);
                foreach ($reflection->getAttributes(Renderer::class) as $attribute) {
                    /** @var Renderer $attr */
                    $attr = $attribute->newInstance();
                    if (strtolower($attr->name) === strtolower($engine)) {
                        $instance = new $className($path);
                        if ($instance instanceof TemplateRendererInterface) {
                            return $instance;
                        }
                    }
                }
            }
        }

        Profiler::stop('factory:renderer');
        throw new ImpulseException("Aucun moteur de rendu trouvé pour '{$engine}'. Assurez-vous qu'un renderer portant #[Renderer('{$engine}')] est disponible.");
    }

    private static function createComponent(array $config): object
    {
        Profiler::start('factory:component');
        $class = $config['class'] ?? null;
        $defaults = $config['defaults'] ?? [];
        $id = $config['id'] ?? null;

        if (!$class || !is_string($class)) {
            throw new \InvalidArgumentException("La clé 'class' est requise et doit être une chaîne pour créer un composant.");
        }

        $obj = ImpulseFactory::create($class, $defaults, $id);
        Profiler::stop('factory:component');
        return $obj;
    }

    /**
     * @throws \JsonException
     */
    private static function resolveTemplatePath(): string
    {
        $configPath = Config::get('template_path', 'views');

        return self::getProjectRoot() . '/' . ltrim($configPath, '/');
    }

    private static function getProjectRoot(): string
    {
        $current = getcwd();

        if (str_contains($current, '/core')) {
            while ($current !== dirname($current)) {
                if (file_exists($current . '/composer.json')) {
                    $composerContent = file_get_contents($current . '/composer.json');
                    if (!str_contains($composerContent, '"name": "impulsephp/core"')) {
                        return $current;
                    }
                }

                $current = dirname($current);
            }
        }

        return getcwd();
    }

    public static function mock(string $type, object $instance, array $config = []): void
    {
        $key = $type . ':' . md5(serialize($config));
        self::$instances[$key] = $instance;
    }

    public static function reset(): void
    {
        self::$instances = [];
    }
}
