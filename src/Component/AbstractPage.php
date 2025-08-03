<?php

declare(strict_types=1);

namespace Impulse\Core\Component;

use Impulse\Core\Support\Collection\ParameterCollection;

abstract class AbstractPage extends AbstractComponent
{
    private ParameterCollection $routeParameters;
    private ParameterCollection $query;

    public function __construct(string $id, string $route, array $params = [], array $query = [])
    {
        $this->routeParameters = new ParameterCollection($params);
        $this->query = new ParameterCollection($query);

        parent::__construct($id, $route);
    }

    public function getRouteParameters(): ParameterCollection
    {
        return $this->routeParameters;
    }

    public function getQuery(): ParameterCollection
    {
        return $this->query;
    }

    public function isScopedStyle(): bool
    {
        return false;
    }

    public function layout(): ?string
    {
        return null;
    }

}
