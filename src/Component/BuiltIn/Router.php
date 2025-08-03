<?php

declare(strict_types=1);

namespace Impulse\Core\Component\BuiltIn;

use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Exceptions\ImpulseException;
use Impulse\Core\Http\Router\PageRouter;

/**
 * @property ?string $name
 */
final class Router extends AbstractComponent
{
    public function setup(): void
    {
        $this->state('name', '');
    }

    public function template(): string
    {
        $router = PageRouter::instance() ?? new PageRouter();
        $href = $router->generate($this->name);
        if ($href === '#') {
            throw new ImpulseException("La route \"{$this->name}\" est inexistante");
        }

        $slot = $this->slot();
        $href = htmlspecialchars($href, ENT_QUOTES);

        return <<<HTML
            <a href="$href" data-router>
                $slot
            </a>
        HTML;
    }
}
