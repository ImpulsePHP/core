<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

use Impulse\Core\Container\ImpulseContainer;

interface ServiceProviderInterface
{
    public function register(ImpulseContainer $container): void;
    public function boot(ImpulseContainer $container): void;
}
