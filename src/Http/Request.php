<?php

declare(strict_types=1);

namespace Impulse\Core\Http;

use Impulse\Core\Support\Collection\ParameterCollection;

final class Request
{
    private string $method;
    private string $uri;
    private ParameterCollection $query;
    private ParameterCollection $request;
    private ParameterCollection $server;

    public function __construct(string $uri, string $method = 'GET', array $query = [], array $request = [], array $server = [])
    {
        $this->uri = $uri;
        $this->method = strtoupper($method);
        $this->query = new ParameterCollection($query);
        $this->request = new ParameterCollection($request);
        $this->server = new ParameterCollection($server);
    }

    public static function createFromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $query = self::parseQueryString($queryString);
        $post = $_POST ?? [];

        return new self($uri, $method, $query, $post, $_SERVER);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getPath(): string
    {
        return parse_url($this->uri, PHP_URL_PATH);
    }

    public function query(): ParameterCollection
    {
        return $this->query;
    }

    public function request(): ParameterCollection
    {
        return $this->request;
    }

    public function server(): ParameterCollection
    {
        return $this->server;
    }

    public function isAjax(): bool
    {
        return strtolower($this->server->get('HTTP_X_REQUESTED_WITH', '')) === 'xmlhttprequest';
    }

    public function expectsJson(): bool
    {
        $accept = strtolower($this->server->get('HTTP_ACCEPT', ''));
        return str_contains($accept, 'application/json');
    }

    public function isJson(): bool
    {
        $contentType = $this->server->get('HTTP_CONTENT_TYPE', '')
            ?: $this->server->get('CONTENT_TYPE', '');

        return str_contains($contentType, 'application/json');
    }

    private static function parseQueryString(string $queryString): array
    {
        if ($queryString === '') {
            return [];
        }

        $query = [];
        foreach (explode('&', $queryString) as $pair) {
            if (!str_contains($pair, '=')) {
                continue;
            }

            [$rawKey, $rawValue] = explode('=', $pair, 2);
            $key = htmlspecialchars(urldecode($rawKey), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $value = htmlspecialchars(urldecode($rawValue), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $query[$key] = $value;
        }

        return $query;
    }

    /**
     * @throws \JsonException
     */
    public static function normalizeJsonPost(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
        ) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded)) {
                $_POST = array_merge($_POST, $decoded);
            }
        }
    }
}
