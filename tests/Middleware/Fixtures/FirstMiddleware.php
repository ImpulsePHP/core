<?php
namespace Impulse\Core\Tests\Middleware\Fixtures;

use Impulse\Core\Contracts\MiddlewareInterface;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

class FirstMiddleware implements MiddlewareInterface
{
    public function __construct(private Logger $logger) {}

    public function handle(Request $request, callable $next): Response
    {
        $this->logger->entries[] = 'first';
        return $next($request);
    }
}
