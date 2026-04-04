<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Support;

use Impulse\Core\DevTools\DevToolsRegistry;
use PHPUnit\Framework\TestCase;

final class DevToolsRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        DevToolsRegistry::clear();
        DevToolsRegistry::setMaxEvents(1000);
    }

    public function testCollectAddsEventWithTypeAndTimestamp(): void
    {
        DevToolsRegistry::clear();

        DevToolsRegistry::collect('http', ['payload' => ['ok' => true]]);
        $events = DevToolsRegistry::all();

        $this->assertCount(1, $events);
        $this->assertSame('http', $events[0]['type']);
        $this->assertArrayHasKey('timestamp', $events[0]);
        $this->assertSame(['ok' => true], $events[0]['payload']);
    }

    public function testCollectTrimsWhenMaxEventsIsReached(): void
    {
        DevToolsRegistry::clear();
        DevToolsRegistry::setMaxEvents(2);

        DevToolsRegistry::collect('debug', ['n' => 1]);
        DevToolsRegistry::collect('debug', ['n' => 2]);
        DevToolsRegistry::collect('debug', ['n' => 3]);

        $events = DevToolsRegistry::all();

        $this->assertCount(2, $events);
        $this->assertSame(3, $events[1]['n']);
    }
}

