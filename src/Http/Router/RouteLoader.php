<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;
use Impulse\Core\Exceptions\ImpulseException;
use Impulse\Core\Support\Profiler;

final class RouteLoader
{
    private string $baseDir;

    /**
     * @var array<string, object>
     */
    private array $routes = [];

    public function __construct(string $baseDir)
    {
        $this->baseDir = realpath($baseDir);
    }

    /**
     * @return array<string, object>
     */
    public function load(): array
    {
        Profiler::start('router:load');

        if ($this->routes !== []) {
            $result = $this->routes;
            Profiler::stop('router:load');
            return $result;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->baseDir));
        foreach ($iterator as $file) {
            if (
                !$file->isFile()
                || $file->getExtension() !== 'php'
                || !str_ends_with($file->getFilename(), 'Page.php')
            ) {
                continue;
            }

            $realPath = $file->getRealPath();
            require_once $realPath;

            $className = pathinfo($realPath, PATHINFO_FILENAME);
            $namespace = $this->getClassNamespace($realPath);
            $fqcn = $namespace ? "$namespace\\$className" : $className;

            if (!class_exists($fqcn)) {
                continue;
            }

            $refClass = new \ReflectionClass($fqcn);
            $attr = $refClass->getAttributes(PageProperty::class);
            if (!$attr) {
                continue;
            }

            /** @var PageProperty $meta */
            $meta = $attr[0]->newInstance();
            $meta->class = $fqcn;
            $meta->file = $realPath;

            if (!is_subclass_of($fqcn, AbstractPage::class)) {
                throw new ImpulseException("L'attribut 'PageProperty' ne peut être utilisé que sur les classes héritant de Impulse\\Core\\Component\\AbstractPage");
            }

            $this->routes[$meta->route] = $meta;
        }

        uksort($this->routes, function(string $a, string $b): int {
            $metaA = $this->routes[$a];
            $metaB = $this->routes[$b];

            if ($metaA->priority !== $metaB->priority) {
                return $metaB->priority <=> $metaA->priority;
            }

            return substr_count($a, '/') <=> substr_count($b, '/');
        });

        $result = $this->routes;
        Profiler::stop('router:load');
        return $result;
    }

    private function getClassNamespace(string $file): ?string
    {
        $src = file_get_contents($file);
        if (preg_match('#^namespace\s+(.+?);$#m', $src, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
