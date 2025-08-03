<?php

declare(strict_types=1);

namespace Impulse\Core\Cache;

interface CacheInterface
{
    public function get(string $key): ?string;
    public function set(string $key, string $content, int $ttl): void;
    public function has(string $key): bool;
    public function delete(string $key): void;
}
