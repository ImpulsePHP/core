<?php

declare(strict_types=1);

namespace Impulse\Core\Middleware;

use Impulse\Core\App;
use Impulse\Core\Contracts\MiddlewareInterface;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

final class MiddlewareDispatcher
{
    /**
     * @param Request $request
     * @param array<class-string<MiddlewareInterface>> $middlewareList
     * @param callable $finalHandler
     * @return Response
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public static function run(Request $request, array $middlewareList, callable $finalHandler): Response
    {
        $container = App::container();

        $pipeline = array_reduce(
            array_reverse($middlewareList),
            static function (callable $next, string $middlewareClass) use ($container) {
                return static function (Request $req) use ($next, $middlewareClass, $container): Response {
                    $instance = $container->make($middlewareClass);
                    if (!$instance instanceof MiddlewareInterface) {
                        throw new \RuntimeException(sprintf('%s must implement %s', $middlewareClass, MiddlewareInterface::class));
                    }

                    return $instance->handle($req, $next);
                };
            },
            $finalHandler
        );

        return $pipeline($request);
    }
}
