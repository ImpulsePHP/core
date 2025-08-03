<?php

namespace Impulse\Core\Tests\Http;

use Impulse\Core\Http\ExceptionHandler;
use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

class ExceptionHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
    }

    public function testDevEnvironmentShowsStacktrace(): void
    {
        Config::set('env', 'dev');
        $handler = new ExceptionHandler();
        $e = new \RuntimeException('Fail');
        $response = $handler->render($e);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Fail', $response->getContent());
        $this->assertStringContainsString($e->getFile(), $response->getContent());
    }

    public function testProdUsesCustomComponentIfAvailable(): void
    {
        require_once __DIR__ . '/Fixtures/Error404Component.php';

        Config::set('env', 'prod');
        $handler = new ExceptionHandler();
        $e = new \RuntimeException('not found', 404);

        $response = $handler->render($e);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('<h1>Not Found</h1>', $response->getContent());
    }

    public function testProdFallsBackToGenericPage(): void
    {
        Config::set('env', 'prod');
        $handler = new ExceptionHandler();
        $e = new \RuntimeException('oops');

        $response = $handler->render($e);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertStringContainsString('Erreur 500', $response->getContent());
    }
}
