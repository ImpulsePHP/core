<?php

declare(strict_types=1);

namespace Impulse\Core\Component;

abstract class AbstractLayout extends AbstractComponent
{
    public function isScopedStyle(): bool
    {
        return false;
    }
}
