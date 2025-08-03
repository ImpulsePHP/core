<?php
namespace Impulse\Core\Tests\Middleware;

use Impulse\Core\App;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;
use Impulse\Core\Middleware\MiddlewareDispatcher;
use Impulse\Core\Tests\Middleware\Fixtures\FirstMiddleware;
use Impulse\Core\Tests\Middleware\Fixtures\Logger;
use Impulse\Core\Tests\Middleware\Fixtures\SecondMiddleware;
use PHPUnit\Framework\TestCase;

class MiddlewareDispatcherTest extends TestCase
{
    public function testMiddlewareChainRunsInOrder(): void
    {
        App::boot();
        $container = App::container();
        $container->registerNamespace('Impulse\\Core\\Tests\\Middleware\\Fixtures', __DIR__ . '/Fixtures');

        /** @var Logger $logger */
        $logger = $container->make(Logger::class);

        $request = new Request('/');

        $response = MiddlewareDispatcher::run(
            $request,
            [FirstMiddleware::class, SecondMiddleware::class],
            fn (Request $req) => Response::json(['ok' => true])
        );

        $this->assertSame(['first', 'second'], $logger->entries);
        $this->assertSame('{"ok":true}', $response->getContent());
    }
}
