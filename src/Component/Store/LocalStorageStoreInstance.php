<?php

declare(strict_types=1);

namespace Impulse\Core\Component\Store;

use Impulse\Core\Contracts\StoreInterface;
use Impulse\Core\Support\LocalStorage;

class LocalStorageStoreInstance implements StoreInterface
{
    private string $name;

    /**
     * @var array<string,mixed>
     */
    private array $data;

    /**
     * @throws \JsonException
     */
    public static function createFromGlobals(string $name): self
    {
        $globalData = LocalStorage::getGlobalData();
        $initial = $globalData[$name] ?? [];
        $data = $_POST;

        if (empty($data) && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $source = $data['_local_storage'][$name] ?? null;
        if (is_string($source) && $source !== '' && $source !== 'undefined') {
            try {
                $decoded = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    $initial = $decoded;
                }
            } catch (\JsonException) {
                // ...
            }
        }

        return new self($name, is_array($initial) ? $initial : []);
    }

    public function __construct(string $name, array $initial = [])
    {
        $this->name = $name;
        $this->data = $initial;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<string,mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}
