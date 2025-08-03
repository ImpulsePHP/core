<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface HasComponentNamespacesInterface
{
    public function getComponentNamespaces(): array;
}
