<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface EventInterface
{
    public function name(): string;
    public function payload(): mixed;
}
