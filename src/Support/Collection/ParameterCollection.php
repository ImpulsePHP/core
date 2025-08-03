<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

final class ParameterCollection
{
    use CollectionTrait;

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->items) ? $this->items[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
