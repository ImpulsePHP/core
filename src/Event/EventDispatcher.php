<?php

declare(strict_types=1);

namespace Impulse\Core\Event;

use Impulse\Core\Contracts\EventDispatcherInterface;
use Impulse\Core\Contracts\EventInterface;
use Impulse\Core\Contracts\ListenerInterface;
use Impulse\Core\Support\Logger;

final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var array<string, array<int, ListenerInterface>>
     */
    private array $listeners = [];
    private array $queuedEvents = [];
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * @throws \JsonException
     */
    public function addListener(string $eventName, ListenerInterface $listener): void
    {
        Logger::debug(
            sprintf('Adding listener for event %s', $eventName),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'event' => $eventName,
            ]
        );

        $this->listeners[$eventName][] = $listener;
    }

    /**
     * @throws \JsonException
     */
    public function dispatch(EventInterface $event): void
    {
        Logger::debug(
            sprintf('Dispatching event %s', $event->name()),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'event' => $event->name(),
            ]
        );

        foreach ($this->listeners[$event->name()] ?? [] as $listener) {
            $listener->handle($event);
        }

        $this->queuedEvents[] = $event;
    }

    /**
     * @throws \JsonException
     */
    public function queue(EventInterface $event): void
    {
        Logger::debug(
            sprintf('Queueing event %s', $event->name()),
            [
                'class' => self::class,
                'method' => __METHOD__,
                'event' => $event->name(),
            ]
        );

        $this->queuedEvents[] = $event;
    }

    /**
     * @throws \JsonException
     */
    public function flush(): array
    {
        Logger::debug('Flushing queued events', [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        $toDispatch = $this->queuedEvents;
        $this->queuedEvents = [];

        return $toDispatch;
    }
}
