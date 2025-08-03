<?php

declare(strict_types=1);

namespace Impulse\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Renderer
{
    public function __construct(
        public string $name,
        public ?string $bundle = null
    ) {}
}
