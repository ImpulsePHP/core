<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

use Impulse\Core\Contracts\ComponentInterface;

/**
 * @implements \IteratorAggregate<string, ComponentInterface>
 */
final class ComponentCollection implements \IteratorAggregate, \Countable
{
    use CollectionTrait;

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function cache(string $id, ComponentInterface $component): self
    {
        return $this->set($id, $component);
    }

    public function getCached(string $id): ?ComponentInterface
    {
        return $this->get($id);
    }

    public function isCached(string $id): bool
    {
        return $this->has($id);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
