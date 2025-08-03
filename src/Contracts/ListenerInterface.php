<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface ListenerInterface
{
    public function handle(EventInterface $event): void;
}
