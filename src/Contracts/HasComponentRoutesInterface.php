<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface HasComponentRoutesInterface
{
    public function getComponentRoutes(): string;
}
