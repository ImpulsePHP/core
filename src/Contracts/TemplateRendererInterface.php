<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface TemplateRendererInterface
{
    public function render(string $template, array $data = []): string;
}
