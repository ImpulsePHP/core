<?php

declare(strict_types=1);

namespace Impulse\Core\Component\Resolver;

use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Exceptions\ComponentResolverException;
use Impulse\Core\Http\Request;
use Impulse\Core\Support\Collection\ComponentCollection;
use Impulse\Core\Support\ComponentNamespaceRegistry;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\DevError;

class ComponentResolver
{
    private static ComponentCollection $componentCache;
    private static array $refreshAttempted = [];

    /**
     * @var array<string, string>
     */
    private static array $index = [];
    private static bool $indexLoaded = false;
    private static array $namespaces = [];

    /**
     * @throws \JsonException
     */
    public static function all(): array
    {
        self::initializeCollections();
        return self::$componentCache->toArray();
    }

    /**
     * @throws \JsonException
     */
    private static function initializeCollections(): void
    {
        if (!isset(self::$componentCache)) {
            Config::load();
            self::$componentCache = new ComponentCollection();
        }

        $names = Config::get('component_namespaces', []);
        foreach ($names as $namespace) {
            if (!in_array($namespace, self::$namespaces, true)) {
                self::$namespaces[] = $namespace;
            }
        }

        self::syncNamespaces();
    }

    private static function getIndexPath(): string
    {
        return getcwd() . '/../var/storage/cache/impulse/component_index.json';
    }

    /**
     * @throws \JsonException
     */
    public static function inject(string $id, ComponentInterface $component): void
    {
        self::initializeCollections();
        self::$componentCache->cache($id, $component);
    }

    /**
     * @throws \JsonException
     */
    private static function loadIndex(): void
    {
        if (self::$indexLoaded) {
            return;
        }

        $file = self::getIndexPath();
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file) ?: '[]', true, 512, JSON_THROW_ON_ERROR);
            if (is_array($data)) {
                self::$index = $data;
            }
        }

        self::$indexLoaded = true;
    }

    /**
     * @throws \JsonException
     */
    private static function saveIndex(): void
    {
        $file = self::getIndexPath();
        $dir = dirname($file);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            return;
        }

        file_put_contents($file, json_encode(self::$index, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
    }

    /**
     * @throws \JsonException|\ReflectionException
     */
    public static function resolve(string $id): ?ComponentInterface
    {
        self::initializeCollections();

        if (self::$componentCache->isCached($id)) {
            return self::$componentCache->getCached($id);
        }

        if (!preg_match('/^([a-z0-9\-]*)/', $id, $matches)) {
            DevError::respond("Format d'ID invalide : $id");
        }

        $prefix = $matches[1];
        if ($prefix === 'layout') {
            return null;
        }

        self::loadIndex();

        $componentClass = self::$index[$prefix] ?? null;

        if (!$componentClass) {
            if (!isset(self::$refreshAttempted[$prefix])) {
                self::$refreshAttempted[$prefix] = true;

                self::refreshIndex();
                self::loadIndex();

                $componentClass = self::$index[$prefix] ?? null;
            }

            if (!$componentClass) {
                $className = self::getClassNameFromPrefix($prefix);
                DevError::respond("Composant non trouvé pour le préfixe : $prefix (classe attendue : $className)");
            }
        }

        if (!$componentClass) {
            DevError::respond("Classe non trouvée pour le préfixe : $prefix");
        }

        try {
            $defaults = [];
            if (func_num_args() > 1 && is_array(func_get_arg(1))) {
                $defaults = func_get_arg(1);
            }

            if ((new \ReflectionClass($componentClass))->isAbstract()) {
                DevError::respond("Classe abstraite ignorée : $componentClass");
            }

            $request = Request::createFromGlobals();
            $component = new $componentClass($id, $request->getUri(), $defaults);

            foreach ($defaults as $key => $value) {
                if (str_starts_with($key, '__slot:')) {
                    $slotName = substr($key, 7);
                    if (is_string($value) && method_exists($component, 'setSlot')) {
                        $component->setSlot($slotName, $value);
                    }
                }
            }

            self::$componentCache->cache($id, $component);

            return $component;
        } catch (ComponentResolverException $e) {
            DevError::respond("Erreur lors de l'instanciation du composant $componentClass : " . $e->getMessage());
        }
    }

    /**
     * @throws \JsonException
     */
    private static function getClassNameFromPrefix(string $prefix): ?string
    {
        self::initializeCollections();
        $parts = explode('-', $prefix);

        return  implode('', array_map('ucfirst', $parts));
    }

    /**
     * @throws \JsonException
     */
    public static function clearCache(): void
    {
        self::initializeCollections();
        self::$componentCache->clear();
    }

    /**
     * @throws \JsonException
     */
    public static function registerNamespaceFromInstance(object $instance): void
    {
        if (!$instance instanceof AbstractComponent) {
            return;
        }

        $class = get_class($instance);

        foreach (self::$namespaces as $namespace) {
            if (str_starts_with($class, rtrim($namespace, '\\'))) {
                return;
            }
        }

        $parts = explode('\\', $class);
        array_pop($parts);

        $keywords = ['Component', 'Page', 'Layout'];
        $base = [];
        foreach ($parts as $part) {
            $base[] = $part;
            if (in_array($part, $keywords, true)) {
                break;
            }
        }

        if (empty($base)) {
            return;
        }

        $namespace = implode('\\', $base) . '\\';

        if (!in_array($namespace, self::$namespaces, true)) {
            self::$namespaces[] = $namespace;

            Config::load();
            $existing = Config::get('component_namespaces', []);
            if (!in_array($namespace, $existing, true) && !Config::isInternalNamespace($namespace)) {
                $existing[] = $namespace;
                Config::set('component_namespaces', $existing);

                try {
                    Config::save();
                } catch (ComponentResolverException|\JsonException $e) {
                    DevError::respond("Erreur lors de la sauvegarde du namespace : " . $e->getMessage());
                }
            }
        }
    }

    /**
     * @throws \JsonException
     */
    public static function refreshIndex(): void
    {
        foreach (self::$namespaces as $namespace) {
            $baseNamespace = rtrim($namespace, '\\');
            $basePath = self::getPathFromNamespace($baseNamespace);
            if (!is_dir($basePath)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $fullPath = $file->getPathname();
                    $relativePath = str_replace([$basePath, '/', '.php'], ['', '\\', ''], $fullPath);
                    $fullClass = $baseNamespace . $relativePath;
                    if (class_exists($fullClass) && is_subclass_of($fullClass, ComponentInterface::class)) {
                        $short = (new \ReflectionClass($fullClass))->getShortName();
                        $short = str_ends_with($short, 'Component') ? substr($short, 0, -9) : $short;
                        $prefix = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $short)) . '-component';
                        self::$index[$prefix] = $fullClass;
                    }
                }
            }
        }

        self::saveIndex();
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public static function allByAjax(): array
    {
        $components = [];
        $componentIds = explode(',', $_SERVER['HTTP_X_IMPULSE_COMPONENTS'] ?? '');

        foreach ($componentIds as $componentId) {
            $componentId = trim($componentId);
            if (empty($componentId)) {
                continue;
            }

            $component = self::resolve($componentId);
            if ($component) {
                $components[$componentId] = $component;
            }
        }

        return $components;
    }

    private static function getPathFromNamespace(string $namespace): string
    {
        static $autoloadMap;

        if (!$autoloadMap) {
            $autoloadMap = require getcwd() . '/../vendor/composer/autoload_psr4.php';
        }

        foreach ($autoloadMap as $nsPrefix => $paths) {
            if (str_starts_with($namespace, rtrim($nsPrefix, '\\'))) {
                $subNamespace = substr($namespace, strlen($nsPrefix));
                $subPath = str_replace('\\', '/', $subNamespace);

                return rtrim($paths[0], '/') . '/' . $subPath;
            }
        }

        throw new \RuntimeException("Impossible de déterminer le chemin pour le namespace : $namespace");
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    public static function findByClass(string $className, array $defaults = []): ?ComponentInterface
    {
        if (!class_exists($className)) {
            DevError::respond("Classe non trouvée : $className");
        }

        if (!(new \ReflectionClass($className))->isSubclassOf(ComponentInterface::class)) {
            DevError::respond("La classe $className n'implémente pas ComponentInterface");
        }

        if ((new \ReflectionClass($className))->isAbstract()) {
            DevError::respond("Classe abstraite ignorée : $className");
        }

        try {
            $id = self::generateUniqueId($className);

            $request = Request::createFromGlobals();
            $component = new $className($id, $request->getUri(), $defaults);

            // Gérer les slots
            foreach ($defaults as $key => $value) {
                if ($key === '__slot') {
                    if (method_exists($component, 'setSlot') && is_string($value)) {
                        $component->setSlot('__slot', $value);
                    }
                } elseif (str_starts_with($key, '__slot:')) {
                    $slotName = substr($key, 7);
                    if (method_exists($component, 'setSlot') && is_string($value)) {
                        $component->setSlot($slotName, $value);
                    }
                }
            }

            return $component;
        } catch (ComponentResolverException $e) {
            DevError::respond("Erreur lors de l'instanciation du composant $className : " . $e->getMessage());
        }
    }

    /**
     * @throws \ReflectionException
     */
    private static function generateUniqueId(string $className): string
    {
        $short = (new \ReflectionClass($className))->getShortName();
        $short = str_ends_with($short, 'Component') ? substr($short, 0, -9) : $short;
        $prefix = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $short)) . '-component';

        // Ajouter un suffixe unique
        return $prefix . '_' . uniqid();
    }

    private static function syncNamespaces(): void
    {
        if (class_exists(ComponentNamespaceRegistry::class)) {
            $allNamespaces = ComponentNamespaceRegistry::getAllNamespaces();

            foreach ($allNamespaces as $namespace) {
                if (!in_array($namespace, self::$namespaces, true)) {
                    self::$namespaces[] = $namespace;
                }
            }
        }
    }
}
