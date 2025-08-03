<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

/**
 * @implements \IteratorAggregate<string, callable[]>
 */
final class WatcherCollection implements \IteratorAggregate, \Countable
{
    use CollectionTrait;

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function set(int|string $key, mixed $item): self
    {
        $this->items[$key][] = $item;

        return $this;
    }
}
