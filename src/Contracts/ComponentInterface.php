<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface ComponentInterface
{
    public function setup(): void;
    public function render(): string;
    public function getComponentId(): string;
    /** @return array<int|string, mixed> */
    public function getStates(): array;
    public function onAfterAction(): void;
    public function onBeforeAction(?string $method = null, array $args = []): void;
}
