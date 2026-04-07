<?php

declare(strict_types=1);

namespace Impulse\Core\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class LayoutProperty
{
    public function __construct(
        public ?string $titlePrefix = null,
        public ?string $titleSuffix = null
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return [
            'prefix' => $this->titlePrefix,
            'suffix' => $this->titleSuffix,
        ];
    }
}

