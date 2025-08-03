<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface ImpulseKernelInterface
{
    public static function boot(): void;
    public static function renderer(): ?TemplateRendererInterface;
}
