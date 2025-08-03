<?php

namespace Impulse\Core\Tests\Cache;

use Impulse\Core\Cache\FileCache;
use PHPUnit\Framework\TestCase;

class FileCacheTest extends TestCase
{
    public function testStoreAndFetch(): void
    {
        $dir = sys_get_temp_dir() . '/impulse_cache_test';
        if (is_dir($dir)) {
            array_map('unlink', glob($dir.'/*'));
        }

        $cache = new FileCache($dir);
        $cache->set('foo', 'bar', 60);
        $this->assertTrue($cache->has('foo'));
        $this->assertSame('bar', $cache->get('foo'));
    }

    public function testExpiration(): void
    {
        $dir = sys_get_temp_dir() . '/impulse_cache_test';
        if (is_dir($dir)) {
            array_map('unlink', glob($dir.'/*'));
        }

        $cache = new FileCache($dir);
        $cache->set('baz', 'qux', -1);
        $this->assertNull($cache->get('baz'));
    }
}
