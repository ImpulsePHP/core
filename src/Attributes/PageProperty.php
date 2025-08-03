<?php

namespace Impulse\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class PageProperty
{
    public function __construct(
        public string $route = '',
        public ?string $name = null,
        public ?string $title = null,
        public ?string $layout = null,
        public bool $auth = false,
        public array $roles = [],
        /** @var array<class-string> */
        public array $middlewares = [],
        public ?bool $cache = null,
        public int $priority = 0,
        public ?string $class = null,
        public ?string $file = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            'route' => $this->route,
            'name' => $this->name,
            'title' => $this->title,
            'layout' => $this->layout,
            'auth' => $this->auth,
            'roles' => $this->roles,
            'middlewares' => $this->middlewares,
            'cache' => $this->cache,
            'priority' => $this->priority,
            'class' => $this->class,
            'file' => $this->file,
        ];
    }
}
