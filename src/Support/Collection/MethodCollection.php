<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

use Impulse\Core\Contracts\MethodCollectionInterface;
use Impulse\Core\Exceptions\MethodCollectionException;

/**
 * @implements \IteratorAggregate<string, callable>
 */
final class MethodCollection implements MethodCollectionInterface, \IteratorAggregate, \Countable
{
    use CollectionTrait;

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function register(string $name, callable $method): self
    {
        return $this->set($name, $method);
    }

    public function call(string $name, array $arguments = []): mixed
    {
        $method = $this->get($name);
        if ($method === null) {
            throw new MethodCollectionException("La méthode '$name' n'existe pas sur ce composant");
        }

        return $method(...$arguments);
    }

    public function exists(string $name): bool
    {
        return $this->has($name);
    }
}
