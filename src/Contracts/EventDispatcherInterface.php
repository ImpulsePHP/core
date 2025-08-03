<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface EventDispatcherInterface
{
    public function addListener(string $eventName, ListenerInterface $listener): void;
    public function dispatch(EventInterface $event): void;
}
