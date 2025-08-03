<?php

namespace Impulse\Core\DevTools;

interface DevToolsEmitterInterface
{
    public function emit(array $event): void;
}
