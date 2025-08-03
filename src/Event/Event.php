<?php

declare(strict_types=1);

namespace Impulse\Core\Event;

use Impulse\Core\Contracts\EventInterface;

final class Event implements EventInterface
{
    private string $name;

    /**
     * @var array<string, mixed>
     */
    private array $payload;

    /**
     * @param string $name
     * @param array<string, mixed>|null $payload
     */
    public function __construct(string $name, ?array $payload = null)
    {
        $this->name = $name;
        $this->payload = $payload ?? [];
    }

    public function name(): string
    {
        return $this->name;
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
