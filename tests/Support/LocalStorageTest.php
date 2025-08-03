<?php

namespace Impulse\Core\Support;

use Impulse\Core\Tests\Support\LocalStorageTest as TestCaseClass;

if (!function_exists(__NAMESPACE__ . '\\file_get_contents')) {
    function file_get_contents(string $filename): string
    {
        return TestCaseClass::$input;
    }
}

namespace Impulse\Core\Tests\Support;

use Impulse\Core\Component\Store\LocalStorageStoreInstance;
use Impulse\Core\Support\LocalStorage;
use PHPUnit\Framework\TestCase;

class LocalStorageTest extends TestCase
{
    public static string $input = '';

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['CONTENT_TYPE'] = '';
        $_POST = [];
        self::$input = '';
    }

    /**
     * @throws \JsonException
     */
    public function testIngestRequestPayloadPopulatesGlobal(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        self::$input = json_encode([
            '_local_storage_payload' => ['store' => '{"foo":"bar"}']
        ], JSON_THROW_ON_ERROR);

        LocalStorage::ingestRequestPayload();

        $this->assertSame(['store' => '{"foo":"bar"}'], $_POST['_local_storage']);
    }

    /**
     * @throws \JsonException
     */
    public function testCreateFromGlobalsLoadsData(): void
    {
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        self::$input = json_encode([
            '_local_storage_payload' => ['store' => '{"foo":"bar"}']
        ], JSON_THROW_ON_ERROR);

        LocalStorage::ingestRequestPayload();
        $store = LocalStorageStoreInstance::createFromGlobals('store');

        $this->assertSame('bar', $store->get('foo'));
    }
}

