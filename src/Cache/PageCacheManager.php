<?php

declare(strict_types=1);

namespace Impulse\Core\Cache;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;
use Impulse\Core\Support\Config;

final class PageCacheManager
{
    private CacheInterface $driver;
    private int $ttl;
    private static bool $disabled = false;

    public static function disable(): void
    {
        self::$disabled = true;
    }

    public static function isDisabled(): bool
    {
        return self::$disabled;
    }

    /**
     * @throws \JsonException
     */
    public function __construct(?CacheInterface $driver = null)
    {
        $this->driver = $driver ?? self::createDriver();
        $this->ttl = (int) Config::get('cache.ttl', 0);
    }

    private static function createDriver(): CacheInterface
    {
        $dir = getcwd() . '/../var/storage/cache/page';
        return new FileCache($dir);
    }

    /**
     * @throws \JsonException
     */
    public function isCacheable(Request $request, ?PageProperty $meta = null): bool
    {
        if (self::$disabled) {
            return false;
        }

        if ($meta && $meta->cache === false) {
            return false;
        }

        if (!Config::get('cache.enabled', false)) {
            return false;
        }

        if ($request->getMethod() !== 'GET') {
            return false;
        }

        foreach ($request->query()->all() as $key => $_) {
            if (in_array($key, ['action', 'update'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @throws \JsonException
     */
    public function get(Request $request, ?PageProperty $meta = null): ?Response
    {
        if (!$this->isCacheable($request, $meta)) {
            return null;
        }

        $key = $this->generateKey($request);
        $html = $this->driver->get($key);
        if ($html === null) {
            return null;
        }

        return Response::html($html);
    }

    /**
     * @throws \JsonException
     */
    public function put(Request $request, string $html, ?PageProperty $meta = null): void
    {
        if (!$this->isCacheable($request, $meta)) {
            return;
        }

        $key = $this->generateKey($request);
        $this->driver->set($key, $html, $this->ttl);
    }

    public function invalidate(string $key): void
    {
        $this->driver->delete($key);
    }

    /**
     * @throws \JsonException
     */
    public function generateKey(Request $request): string
    {
        $parts = [
            $request->getPath(),
            http_build_query($request->query()->all()),
            (string) Config::get('locale', ''),
        ];

        return sha1(implode('|', $parts));
    }
}
