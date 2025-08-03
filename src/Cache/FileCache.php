<?php

declare(strict_types=1);

namespace Impulse\Core\Cache;

final class FileCache implements CacheInterface
{
    private string $directory;

    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? getcwd() . '/../var/storage/cache/page';
    }

    private function getPath(string $key): string
    {
        return rtrim($this->directory, '/').'/'.sha1($key).'.json';
    }

    /**
     * @throws \JsonException
     */
    public function get(string $key): ?string
    {
        $path = $this->getPath($key);
        if (!is_file($path)) {
            return null;
        }

        $data = json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return null;
        }

        if (($data['expire'] ?? 0) < time()) {
            @unlink($path);
            return null;
        }

        return (string)($data['content'] ?? '');
    }

    /**
     * @throws \JsonException
     */
    public function set(string $key, string $content, int $ttl): void
    {
        $path = $this->getPath($key);
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        $data = ['expire' => time() + $ttl, 'content' => $content];
        file_put_contents($path, json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws \JsonException
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        $path = $this->getPath($key);
        if (is_file($path)) {
            unlink($path);
        }
    }
}
