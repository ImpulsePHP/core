<?php

declare(strict_types=1);

namespace Impulse\Core\Http\Router;

use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;
use Impulse\Core\Http\ExceptionHandler;
use Impulse\Core\Support\Config;
use Impulse\Core\Middleware\MiddlewareDispatcher;
use Impulse\Core\Cache\PageCacheManager;
use Impulse\DevTools\Exception\PageResolverException;
use ScssPhp\ScssPhp\Exception\SassException;
use Impulse\Core\Support\Profiler;
use Impulse\Core\Support\Logger;
use Impulse\Core\DevTools\Collectors\HttpCollector;
use Impulse\Core\DevTools\Collectors\RouteCollector;
use Impulse\Core\DevTools\Collectors\HttpRequestCollector;

final class PageRouter
{
    private static ?self $instance = null;
    private array $routes;
    private LayoutManager $layoutManager;
    private HtmlResponse $htmlResponse;
    private array $compiledPatterns = [];
    private PageCacheManager $cacheManager;

    /**
     * @throws \JsonException
     */
    public function __construct(?string $baseDir = null)
    {
        Logger::debug('Initializing PageRouter', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        $baseDir = $baseDir ?? getcwd() . '/../src/Page';
        $baseDir = realpath($baseDir);

        $routeLoader = new RouteLoader($baseDir);
        $this->layoutManager = new LayoutManager();
        $this->htmlResponse = new HtmlResponse();
        $this->cacheManager = new PageCacheManager();

        $this->routes = $routeLoader->load();
        $this->routes = array_merge($this->routes, RouteRegistry::getAllRoutes());

        self::$instance = $this;
    }

    /**
     * @throws \JsonException
     */
    public function handle(Request $request): void
    {
        Profiler::start('router:handle');
        $start = microtime(true);
        $reqHeaders = $request->server()->all();
        $reqBody = $request->request()->all();
        Logger::debug(
            sprintf('Handling request %s %s', $request->getMethod(), $request->getUri()),
            [
                'class' => self::class,
                'method' => __METHOD__,
            ]
        );

        try {
                $uri = $this->normalizeUri($request->getUri());

                foreach ($this->routes as $route => $meta) {
                    if (!isset($this->compiledPatterns[$route])) {
                        $this->compiledPatterns[$route] = '#^' . preg_replace('#\[:(\w+)]#', '(?<$1>[^/]+)', $route) . '$#';
                    }

                    if (preg_match($this->compiledPatterns[$route], $uri, $matches)) {
                        Logger::debug(
                            sprintf('Matched route %s', $route),
                            [
                                'class' => self::class,
                                'method' => __METHOD__,
                                'route' => $route,
                            ]
                        );

                        $middlewares = array_merge(
                            Config::get('middlewares', []),
                            $meta->middlewares ?? []
                        );

                        $cached = $this->cacheManager->get($request, $meta);
                        if ($cached) {
                            Logger::debug('Serving cached response', [
                                'class' => self::class,
                                'method' => __METHOD__,
                            ]);

                            $cached->send();

                            RouteCollector::record(
                                ['route' => $route],
                                [
                                    'file' => $meta->file,
                                    'component' => $meta->class,
                                    'layout' => $meta->layout,
                                    'name' => $meta->name,
                                    'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
                                ]
                            );

                            HttpCollector::record(
                                ['route' => $route, 'method' => $request->getMethod()],
                                [
                                    'status' => $cached->getStatusCode(),
                                    'duration' => (int) ((microtime(true) - $start) * 1000),
                                    'ip' => $request->server()->get('REMOTE_ADDR', ''),
                                ]
                            );

                            HttpRequestCollector::record(
                                ['method' => $request->getMethod(), 'url' => $request->getUri()],
                                [
                                    'status' => $cached->getStatusCode(),
                                    'duration' => (int) ((microtime(true) - $start) * 1000),
                                    'request_headers' => $reqHeaders,
                                    'response_headers' => $cached->getHeaders(),
                                    'body' => $reqBody,
                                ]
                            );

                            Profiler::stop('router:handle');
                            return;
                        }

                        $response = MiddlewareDispatcher::run(
                            $request,
                            $middlewares,
                            function (Request $req) use ($meta, $matches) {
                                return $this->renderPage($req, $meta, $matches);
                            }
                        );

                        $this->cacheManager->put($request, $response->getContent(), $meta);

                        Logger::debug('Response cached', [
                            'class' => self::class,
                            'method' => __METHOD__,
                        ]);

                        $response->send();

                        RouteCollector::record(
                            ['route' => $route],
                            [
                                'file' => $meta->file,
                                'component' => $meta->class,
                                'layout' => $meta->layout,
                                'name' => $meta->name,
                                'params' => array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY),
                            ]
                        );

                        HttpCollector::record(
                            ['route' => $route, 'method' => $request->getMethod()],
                            [
                                'status' => $response->getStatusCode(),
                                'duration' => (int) ((microtime(true) - $start) * 1000),
                                'ip' => $request->server()->get('REMOTE_ADDR', ''),
                            ]
                        );

                        HttpRequestCollector::record(
                            ['method' => $request->getMethod(), 'url' => $request->getUri()],
                            [
                                'status' => $response->getStatusCode(),
                                'duration' => (int) ((microtime(true) - $start) * 1000),
                                'request_headers' => $reqHeaders,
                                'response_headers' => $response->getHeaders(),
                                'body' => $reqBody,
                            ]
                        );

                        Profiler::stop('router:handle');
                        return;
                    }
                }

                Logger::debug('No route matched', [
                    'class' => self::class,
                    'method' => __METHOD__,
                ]);

                $this->renderNotFound();
                HttpCollector::record(
                    ['route' => $request->getUri(), 'method' => $request->getMethod()],
                    [
                        'status' => 404,
                        'duration' => (int) ((microtime(true) - $start) * 1000),
                        'ip' => $request->server()->get('REMOTE_ADDR', ''),
                    ]
                );
                HttpRequestCollector::record(
                    ['method' => $request->getMethod(), 'url' => $request->getUri()],
                    [
                        'status' => 404,
                        'duration' => (int) ((microtime(true) - $start) * 1000),
                        'request_headers' => $reqHeaders,
                        'response_headers' => [],
                        'body' => $reqBody,
                    ]
                );
                Profiler::stop('router:handle');
        } catch (\Throwable $e) {
            $handler = new ExceptionHandler();
            $handler->render($e)->send();
            HttpCollector::record(
                ['route' => $request->getUri(), 'method' => $request->getMethod()],
                [
                    'status' => 500,
                    'duration' => (int) ((microtime(true) - $start) * 1000),
                    'ip' => $request->server()->get('REMOTE_ADDR', ''),
                ]
            );
            HttpRequestCollector::record(
                ['method' => $request->getMethod(), 'url' => $request->getUri()],
                [
                    'status' => 500,
                    'duration' => (int) ((microtime(true) - $start) * 1000),
                    'request_headers' => $reqHeaders,
                    'response_headers' => [],
                    'body' => $reqBody,
                ]
            );
            Profiler::stop('router:handle');
        }
    }

    private function normalizeUri(string $uri): string
    {
        return rtrim(parse_url($uri, PHP_URL_PATH) ?? '/', '/') ?: '/';
    }

    /**
     * @throws \JsonException
     * @throws SassException
     * @throws \ReflectionException
     * @throws \DOMException
     * @throws PageResolverException
     */
    public function renderPage(Request $request, object $meta, array $matches): Response
    {
        Logger::debug(
            sprintf('Rendering page %s', $meta->class),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'page' => $meta->class,
            ]
        );

        Profiler::start('router:render:' . $meta->class);
        $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        $query = $request->query()->all();

        $pageKebabCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $meta->class));
        $pageId = 'page_' . strtolower(str_replace('\\', '_', $pageKebabCase));
        $page = new $meta->class($pageId, $request->getUri(), $params, $query);

        $layoutClass = $this->layoutManager->determine($page, $meta);
        $bodyContent = $page->render();

        if ($layoutClass) {
            $bodyContent = $this->layoutManager->apply($layoutClass, $bodyContent, $request->getUri());
        }

        $html = $this->htmlResponse->render($meta, $bodyContent);

        Profiler::stop('router:render:' . $meta->class);
        Logger::debug(
            sprintf('Page %s rendered', $meta->class),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'page' => $meta->class,
            ]
        );

        return Response::html($html);
    }

    /**
     * @throws \JsonException
     */
    private function renderNotFound(): void
    {
        Logger::debug('Rendering 404 page', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        throw new \RuntimeException('Page non trouvée', 404);
    }

    public static function instance(): ?self
    {
        return self::$instance;
    }

    public function generate(string $name, array $params = []): string
    {
        foreach ($this->routes as $route => $meta) {
            if (($meta->name ?? null) === $name) {
                $url = $route;
                foreach ($params as $key => $value) {
                    $url = str_replace('[:' . $key . ']', urlencode((string) $value), $url);
                }

                return $url;
            }
        }

        return '#';
    }

    public function findComponentForRoute(string $route): ?PageProperty
    {
        foreach ($this->routes as $pattern => $meta) {
            if ($pattern === $route && isset($meta->class)) {
                return $meta;
            }
        }

        return null;
    }

    /**
     * @throws \JsonException
     */
    public function addRoutes(array $routes): void
    {
        Logger::debug(
            sprintf('Adding %d routes', count($routes)),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'routes' => count($routes),
            ]
        );

        $this->routes = array_merge($this->routes, $routes);

        uksort($this->routes, function(string $a, string $b): int {
            $metaA = $this->routes[$a];
            $metaB = $this->routes[$b];

            if ($metaA->priority !== $metaB->priority) {
                return $metaB->priority <=> $metaA->priority;
            }

            return substr_count($a, '/') <=> substr_count($b, '/');
        });

        $this->compiledPatterns = [];
    }

    /**
     * @throws \JsonException
     */
    public static function addRoutesStatic(array $routes): void
    {
        if (self::$instance === null) {
            throw new \RuntimeException('PageRouter instance not initialized');
        }

        Logger::debug('Adding routes via static method', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        self::$instance->addRoutes($routes);
    }
}
