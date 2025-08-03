<?php

declare(strict_types=1);

namespace Impulse\Core\Factory;

use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Component\AbstractLayout;
use Impulse\Core\Component\AbstractPage;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Http\Request;
use Impulse\Core\Support\Collection\ComponentCollection;

class ImpulseFactory
{
    /**
     * @var array<string, int>
     */
    private static array $classInstanceCounts = [];
    private static ComponentCollection $instances;

    private static function initializeInstances(): void
    {
        if (!isset(self::$instances)) {
            self::$instances = new ComponentCollection();
        }
    }

    /**
     * @template T of AbstractComponent
     * @param class-string<T> $componentClass
     * @param array<int, mixed> $defaults
     * @param string|null $id
     * @return ComponentInterface
     */
    public static function create(string $componentClass, array $defaults = [], ?string $id = null): ComponentInterface
    {
        self::initializeInstances();

        if (!class_exists($componentClass)) {
            throw new \InvalidArgumentException("La classe de composant '$componentClass' n'existe pas");
        }

        if (!is_subclass_of($componentClass, ComponentInterface::class)) {
            throw new \InvalidArgumentException("La classe '$componentClass' n'est pas un composant valide");
        }

        if (!$id) {
            $reflection = new \ReflectionClass($componentClass);
            $componentClass = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $componentClass));

            if ($reflection->isSubclassOf(AbstractPage::class)) {
                $id = 'page_' . strtolower(str_replace('\\', '_', $componentClass));
            } elseif ($reflection->isSubclassOf(AbstractLayout::class)) {
                $id = 'layout_' . strtolower(str_replace('\\', '_', $componentClass));
            } else {
                $baseId = strtolower($reflection->getShortName());

                if (!isset(self::$classInstanceCounts[$baseId])) {
                    self::$classInstanceCounts[$baseId] = 1;
                } else {
                    self::$classInstanceCounts[$baseId]++;
                }

                $id = $baseId . '_' . self::$classInstanceCounts[$baseId];
            }
        }

        $request = Request::createFromGlobals();
        $component = new $componentClass($id, $request->getUri(), $defaults);

        if (
            $component instanceof ComponentInterface
            && property_exists($component, 'slot')
            && isset($defaults['__slot'])
        ) {
            $component->setSlot('__slot', $defaults['__slot']);
        }

        self::$instances->cache($id, $component);

        return $component;
    }
}
