<?php

namespace Impulse\Core\Tests\Cache;

use Impulse\Core\Cache\FileCache;
use Impulse\Core\Cache\PageCacheManager;
use Impulse\Core\Http\Request;
use PHPUnit\Framework\TestCase;

class PageCacheManagerTest extends TestCase
{
    public function testDisableSkipsCaching(): void
    {
        $dir = sys_get_temp_dir() . '/page_cache_manager_test';
        if (is_dir($dir)) {
            array_map('unlink', glob($dir.'/*'));
        }

        $manager = new PageCacheManager(new FileCache($dir));
        PageCacheManager::disable();
        $request = new Request('/');
        $manager->put($request, '<html>foo</html>');
        $this->assertNull($manager->get($request));
    }
}
