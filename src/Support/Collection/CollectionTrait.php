<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

trait CollectionTrait
{
    protected array $items = [];

    public function all(): array
    {
        return $this->items;
    }

    public function set(int|string $key, mixed $item): self
    {
        $this->items[$key] = $item;

        return $this;
    }

    public function get(mixed $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function add(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function clear(): void
    {
        $this->items = [];
    }

    public function has(mixed $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}
