<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

use Impulse\Core\Exceptions\ConfigException;

final class Config
{
    private static array $data = [];
    private static ?string $mainPath = null;
    private static bool $loaded = false;
    private static array $loadedPaths = [];

    /** @var string[] */
    private static array $internalNamespaces = [
        'Impulse\\Core\\Component\\',
        'Impulse\\Core\\Renderer\\',
    ];

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * @throws \JsonException
     */
    public static function load(?string $path = null): void
    {
        if (self::$loaded && $path === null) {
            Logger::debug('Config already loaded', [
                'class' => self::class,
                'method' => __METHOD__,
            ]);
            return;
        }

        $targetPath = $path ?? self::discoverPath();
        Logger::info(
            sprintf('Loading config from %s', $targetPath),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'path' => $targetPath,
            ]
        );

        if (!self::$loaded) {
            self::$mainPath = $targetPath;
            self::$loaded = true;
        }

        if (in_array($targetPath, self::$loadedPaths, true)) {
            return;
        }

        if ($targetPath && file_exists($targetPath)) {
            try {
                $data = require $targetPath;
                if (is_array($data)) {
                    Logger::debug(
                        sprintf('Merging config from %s', $targetPath),
                        [
                            'class' => self::class,
                            'method' => __METHOD__,
                            'path' => $targetPath,
                        ]
                    );

                    self::mergeConfig($data);
                    self::$loadedPaths[] = $targetPath;
                }
            } catch (\Throwable) {
                Logger::error(
                    sprintf('Failed loading config file %s', $targetPath),
                    [
                        'class' => self::class,
                        'method' => __METHOD__,
                        'path' => $targetPath,
                    ]
                );
                // Ignorer les erreurs de chargement pour les configs optionnelles
            }
        }

        if (count(self::$loadedPaths) === 1) {
            self::initializeDefaults();
            Logger::debug('Initialized default configuration values', [
                'class' => self::class,
                'method' => __METHOD__,
            ]);
        }
    }

    /**
     * @throws \JsonException
     */
    public static function loadProviderConfig(string $path, string $prefix = ''): void
    {
        if (!file_exists($path) || in_array($path, self::$loadedPaths, true)) {
            Logger::debug(
                sprintf('Provider config %s already loaded', $path),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'path' => $path,
                ]
            );
            return;
        }

        try {
            $data = require $path;
            if (is_array($data)) {
                Logger::info(
                    sprintf('Loading provider config %s', $path),
                    [
                        'class' => self::class,
                        'method' => __METHOD__,
                        'path' => $path,
                    ]
                );

                if ($prefix) {
                    $prefixedData = [];
                    foreach ($data as $key => $value) {
                        $prefixedData[$prefix . '.' . $key] = $value;
                    }
                    self::mergeConfig($prefixedData);
                } else {
                    self::mergeConfig($data);
                }
                self::$loadedPaths[] = $path;
            }
        } catch (\Throwable $e) {
            DevError::respond("Erreur lors du chargement de la config provider {$path} : " . $e->getMessage());
        }
    }

    private static function mergeConfig(array $newData): void
    {
        foreach ($newData as $key => $value) {
            if (isset(self::$data[$key])) {
                if (is_array(self::$data[$key]) && is_array($value)) {
                    if (str_contains($key, 'namespace')) {
                        self::$data[$key] = array_values(array_unique(array_merge(self::$data[$key], $value)));
                    } else {
                        self::$data[$key] = array_merge_recursive(self::$data[$key], $value);
                    }
                } else {
                    self::$data[$key] = $value;
                }
            } else {
                self::$data[$key] = $value;
            }
        }
    }

    private static function initializeDefaults(): void
    {
        if (!isset(self::$data['component_namespaces'])) {
            self::$data['component_namespaces'] = [];
        }

        if (!in_array('App\\Component\\', self::$data['component_namespaces'], true)) {
            self::$data['component_namespaces'][] = 'App\\Component\\';
        }

        self::$data['component_namespaces'] = array_values(array_unique(self::$data['component_namespaces']));
    }

    private static function discoverPath(): string
    {
        $candidates = [
            getcwd() . '/../../impulse.php',
            getcwd() . '/../impulse.php',
            getcwd() . '/impulse.php',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0];
    }

    /**
     * @throws \JsonException
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::load();
        }

        $value = self::getNestedValue($key, $default);

        if ($key === 'component_namespaces') {
            $value = array_values(array_unique(array_merge(
                $value ?? [],
                self::$internalNamespaces
            )));
        }

        return $value;
    }

    private static function getNestedValue(string $key, mixed $default = null): mixed
    {
        if (isset(self::$data[$key])) {
            return self::$data[$key];
        }

        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = self::$data;

            foreach ($keys as $k) {
                if (is_array($value) && isset($value[$k])) {
                    $value = $value[$k];
                } else {
                    return $default;
                }
            }

            return $value;
        }

        return $default;
    }

    /**
     * @throws \JsonException
     */
    public static function set(string $key, mixed $value, bool $append = false): void
    {
        if (!self::$loaded) {
            self::load();
        }

        Logger::debug(
            sprintf('Setting config %s (append: %s)', $key, $append ? 'true' : 'false'),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'key' => $key,
                'append' => $append,
            ]
        );

        if (str_contains($key, '.')) {
            self::setNestedValue($key, $value, $append);
        } else {
            self::setDirectValue($key, $value, $append);
        }
    }

    /**
     * @throws \JsonException
     */
    public static function append(string $key, mixed $value): void
    {
        self::set($key, $value, true);
    }

    /**
     * @throws \JsonException
     */
    public static function appendMultiple(string $key, array $values): void
    {
        if (!self::$loaded) {
            self::load();
        }

        $currentValue = self::get($key, []);

        if (!is_array($currentValue)) {
            throw new ConfigException(sprintf('Cannot append to non-array config key "%s"', $key));
        }

        $newValue = array_merge($currentValue, $values);

        if (str_contains($key, 'namespace') || str_contains($key, 'component')) {
            $newValue = array_values(array_unique($newValue));
        }

        self::set($key, $newValue, false);
    }

    private static function setDirectValue(string $key, mixed $value, bool $append): void
    {
        if ($append && isset(self::$data[$key])) {
            if (is_array(self::$data[$key])) {
                if (is_array($value)) {
                    $merged = array_merge(self::$data[$key], $value);

                    if (str_contains($key, 'namespace')) {
                        $merged = array_values(array_unique($merged));
                    }

                    self::$data[$key] = $merged;
                } else {
                    self::$data[$key][] = $value;
                }
            } else {
                throw new ConfigException(sprintf('Cannot append to non-array config key "%s"', $key));
            }
        } else {
            self::$data[$key] = $value;
        }
    }

    private static function setNestedValue(string $key, mixed $value, bool $append): void
    {
        $keys = explode('.', $key);
        $target = &self::$data;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $k = $keys[$i];
            if (!isset($target[$k]) || !is_array($target[$k])) {
                $target[$k] = [];
            }
            $target = &$target[$k];
        }

        $finalKey = end($keys);

        if ($append && isset($target[$finalKey])) {
            if (is_array($target[$finalKey])) {
                if (is_array($value)) {
                    $merged = array_merge($target[$finalKey], $value);

                    if (str_contains($key, 'namespace')) {
                        $merged = array_values(array_unique($merged));
                    }

                    $target[$finalKey] = $merged;
                } else {
                    $target[$finalKey][] = $value;
                }
            } else {
                throw new ConfigException(sprintf('Cannot append to non-array config key "%s"', $key));
            }
        } else {
            $target[$finalKey] = $value;
        }
    }

    /**
     * @throws \JsonException
     */
    public static function save(?string $path = null): void
    {
        if (!self::$loaded) {
            self::load($path);
        }

        $path = $path ?? self::$mainPath ?? self::discoverPath();
        Logger::info(
            sprintf('Saving configuration to %s', $path),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'path' => $path,
            ]
        );

        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new ConfigException(sprintf('Directory "%s" was not created', $dir));
        }

        $data = self::$data;

        if (isset($data['component_namespaces'])) {
            $data['component_namespaces'] = array_values(array_diff(
                $data['component_namespaces'],
                self::$internalNamespaces
            ));
        }

        $export = "<?php\n\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($path, $export);
    }

    /**
     * @throws \JsonException
     */
    public static function reset(): void
    {
        Logger::info('Resetting configuration', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        self::$data = [];
        self::$mainPath = null;
        self::$loaded = false;
        self::$loadedPaths = [];
    }

    public static function isInternalNamespace(string $namespace): bool
    {
        $namespace = rtrim($namespace, '\\') . '\\';
        return in_array($namespace, self::$internalNamespaces, true);
    }

    /** @return string[] */
    public static function getInternalNamespaces(): array
    {
        return self::$internalNamespaces;
    }

    public static function getLoadedPaths(): array
    {
        return self::$loadedPaths;
    }

    /**
     * @throws \JsonException
     */
    public static function all(): array
    {
        if (!self::$loaded) {
            self::load();
        }
        return self::$data;
    }
}
