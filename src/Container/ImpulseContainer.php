<?php

declare(strict_types=1);

namespace Impulse\Core\Container;

use Impulse\Core\Support\Logger;

final class ImpulseContainer
{
    /**
     * @var array<string, array{factory: callable(self): mixed, singleton: bool}>
     */
    private array $definitions = [];

    /**
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * @var array<string, bool>
     */
    private array $resolving = [];

    /**
     * @throws \Exception
     */
    public function make(string $class): object
    {
        Logger::debug(
            sprintf('Making instance of %s', $class),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'target' => $class,
            ]
        );

        // Le conteneur ne peut pas se créer lui-même
        if ($class === self::class) {
            return $this;
        }

        if ($this->has($class)) {
            Logger::debug(
                sprintf('%s is already registered, returning existing service', $class),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'service' => $class,
                ]
            );

            return $this->get($class);
        }

        // Détection de dépendance circulaire
        if (isset($this->resolving[$class])) {
            throw new \InvalidArgumentException(
                sprintf('Circular dependency detected for class "%s"', $class)
            );
        }

        $this->resolving[$class] = true;

        try {
            $ref = new \ReflectionClass($class);
            $ctor = $ref->getConstructor();

            if (!$ctor) {
                Logger::debug(
                    sprintf('Instantiating %s without constructor', $class),
                    [
                        'class' => self::class,
                        'method' => __METHOD__,
                        'target' => $class,
                    ]
                );

                unset($this->resolving[$class]);
                return new $class();
            }

            $args = [];
            foreach ($ctor->getParameters() as $param) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                    $depClass = $type->getName();

                    // Si c'est le conteneur lui-même, on passe $this
                    if ($depClass === self::class) {
                        $args[] = $this;
                    } else {
                        $args[] = $this->has($depClass)
                            ? $this->get($depClass)
                            : $this->make($depClass);
                    }
                } elseif ($param->isDefaultValueAvailable()) {
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new \InvalidArgumentException(
                        sprintf('Cannot resolve parameter "%s" for class "%s"', $param->getName(), $class)
                    );
                }
            }

            $instance = $ref->newInstanceArgs($args);
            unset($this->resolving[$class]);
            return $instance;
        } catch (\Exception $e) {
            unset($this->resolving[$class]);
            throw $e;
        }
    }

    /**
     * @see make()
     * @throws \JsonException
     */
    public function registerNamespace(string $namespace, string $directory): void
    {
        Logger::debug(
            sprintf('Registering namespace %s in %s', $namespace, $directory),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'namespace' => $namespace,
                'directory' => $directory,
            ]
        );

        $namespace = rtrim($namespace, '\\') . '\\';
        $directory = rtrim($directory, '/') . '/';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($directory));
            $relative = str_replace('/', '\\', substr($relative, 0, -4));
            $class = $namespace . $relative;

            if (class_exists($class) && $class !== self::class) {
                $this->set($class, fn (self $c) => $c->make($class));
            }
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        if (is_array($callable)) {
            $target = (is_object($callable[0]) ? get_class($callable[0]) : $callable[0]) . '::' . $callable[1];
        } elseif (is_string($callable)) {
            $target = $callable;
        } else {
            $target = 'closure';
        }

        Logger::debug(
            sprintf('Calling %s', $target),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'callable' => $target,
            ]
        );

        if (is_array($callable)) {
            $object = is_object($callable[0]) ? $callable[0] : $this->make($callable[0]);
            $reflection = new \ReflectionMethod($object, $callable[1]);
        } else {
            $reflection = new \ReflectionFunction($callable);
            $object = null;
        }

        $args = [];
        foreach ($reflection->getParameters() as $param) {
            if (array_key_exists($param->getName(), $parameters)) {
                $args[] = $parameters[$param->getName()];
                continue;
            }

            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $depClass = $type->getName();

                if ($depClass === self::class) {
                    $args[] = $this;
                } else {
                    if (isset($this->resolving[$depClass])) {
                        throw new \InvalidArgumentException(
                            sprintf('Circular dependency detected for class "%s" in call()', $depClass)
                        );
                    }

                    $args[] = $this->has($depClass)
                        ? $this->get($depClass)
                        : $this->make($depClass);
                }
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Cannot resolve parameter "%s"', $param->getName())
                );
            }
        }

        return $reflection->invokeArgs($object, $args);
    }

    /**
     * @throws \JsonException
     */
    public function set(string $id, callable $factory, bool $singleton = true): void
    {
        Logger::debug(
            sprintf('Registering service %s', $id),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'service' => $id,
            ]
        );

        $this->definitions[$id] = ['factory' => $factory, 'singleton' => $singleton];
    }

    /**
     * @throws \JsonException
     */
    public function has(string $id): bool
    {
        $result = isset($this->definitions[$id]) || isset($this->instances[$id]);
        Logger::debug(
            sprintf('Checking service %s exists: %s', $id, $result ? 'true' : 'false'),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'service' => $id,
            ]
        );

        return $result;
    }

    /**
     * @throws \Exception
     */
    public function get(string $id): mixed
    {
        Logger::debug(
            sprintf('Resolving service %s', $id),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'service' => $id,
            ]
        );
        if (isset($this->instances[$id])) {
            Logger::debug(
                sprintf('Returning existing instance of %s', $id),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'service' => $id,
                ]
            );

            return $this->instances[$id];
        }

        if (!isset($this->definitions[$id])) {
            throw new \InvalidArgumentException(sprintf('Service "%s" is not registered.', $id));
        }

        if (isset($this->resolving[$id])) {
            throw new \InvalidArgumentException(
                sprintf('Circular dependency detected for service "%s" in get()', $id)
            );
        }

        $this->resolving[$id] = true;

        try {
            $service = ($this->definitions[$id]['factory'])($this);

            if ($this->definitions[$id]['singleton']) {
                $this->instances[$id] = $service;
            }

            unset($this->resolving[$id]);
            Logger::debug(
                sprintf('Service %s instantiated', $id),
                [
                    'class' => self::class,
                    'method' => __METHOD__,
                    'service' => $id,
                ]
            );

            return $service;
        } catch (\Exception $e) {
            unset($this->resolving[$id]);
            throw $e;
        }
    }

    public function reset(): void
    {
        $this->definitions = [];
        $this->instances = [];
        $this->resolving = [];
    }
}
