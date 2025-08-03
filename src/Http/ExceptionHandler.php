<?php

declare(strict_types=1);

namespace Impulse\Core\Http;

use Impulse\Core\Contracts\ExceptionHandlerInterface;
use Impulse\Core\Http\Router\PageRouter;
use Impulse\Core\Support\Config;
use Impulse\Core\Support\Logger;
use Impulse\Core\DevTools\Collectors\ExceptionCollector;
use Impulse\Core\Attributes\PageProperty;

final class ExceptionHandler implements ExceptionHandlerInterface
{
    /**
     * @throws \JsonException
     */
    public function render(\Throwable $e): Response
    {
        $status = $this->getStatusCode($e);
        $isDev = Config::get('env', 'prod') === 'dev';
        Logger::error($e->getMessage(), [
            'class' => self::class,
            'method' => __METHOD__,
            'exception' => $e::class,
        ]);

        ExceptionCollector::record($e);

        if ($isDev) {
            $content = <<<HTML
                <h1>Erreur</h1>
                <p><strong>{$e->getMessage()}</strong></p>
                <pre>{$e->getFile()}:{$e->getLine()}</pre>
                <pre>{$e->getTraceAsString()}</pre>
            HTML;

            return Response::html($content, $status);
        }

        $componentClass = "\\App\\Component\\Errors\\Error{$status}Component";
        if (class_exists($componentClass)) {
            try {
                $meta = $this->extractPageProperty($componentClass);
                $meta->route = '';

                $request = new Request('GET', '/', [], [], []);

                $router = PageRouter::instance();
                if ($router) {
                    return $router->renderPage($request, $meta, []);
                }
            } catch (\Throwable $renderException) {
                Logger::error(
                    'Error rendering error page: ' . $renderException->getMessage(),
                    [
                        'class' => self::class,
                        'method' => __METHOD__,
                        'exception' => $renderException::class,
                    ]
                );

                if ($isDev) {
                    $content = <<<HTML
                        <h1>Erreur de rendu de la page d'erreur</h1>
                        <p><strong>Erreur originale:</strong> {$e->getMessage()}</p>
                        <p><strong>Erreur de rendu:</strong> {$renderException->getMessage()}</p>
                        <pre>{$renderException->getTraceAsString()}</pre>
                    HTML;

                    return Response::html($content, $status);
                }
            }
        }

        Logger::error('Falling back to generic error page', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        return Response::html("<h1>Erreur {$status}</h1><p>Une erreur est survenue.</p>", $status);
    }

    private function getStatusCode(\Throwable $e): int
    {
        $code = $e->getCode();
        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }

    /**
     * @throws \ReflectionException
     */
    private function extractPageProperty(string $className): PageProperty
    {
        $reflection = new \ReflectionClass($className);
        $attributes = $reflection->getAttributes(PageProperty::class);

        if (empty($attributes)) {
            return new PageProperty(class: $className);
        }

        $attribute = $attributes[0]->newInstance();
        if (!$attribute->class) {
            $attribute->class = $className;
        }

        return $attribute;
    }
}
