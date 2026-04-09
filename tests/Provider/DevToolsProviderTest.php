<?php

declare(strict_types=1);

namespace Impulse\Core\Tests\Provider;

use Impulse\Core\Bootstrap\Kernel;
use Impulse\Core\DevTools\DevToolsEmitterInterface;
use Impulse\Core\DevTools\EventCollector;
use Impulse\Core\DevTools\SocketEmitter;
use Impulse\Core\Provider\DevToolsProvider;
use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

final class DevToolsProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::reset();
        $this->resetEventCollector();
    }

    protected function tearDown(): void
    {
        $this->resetEventCollector();
        Config::reset();

        parent::tearDown();
    }

    public function testProviderRegistersEmitterService(): void
    {
        Config::set('env', 'dev');
        Config::set('devtools', true);

        $kernel = new Kernel([new DevToolsProvider()]);

        $container = $kernel->getContainer();
        $this->assertTrue($container->has(DevToolsEmitterInterface::class));
        $this->assertInstanceOf(SocketEmitter::class, $container->get(DevToolsEmitterInterface::class));
    }

    public function testProviderBootInitializesCollectorWhenEnabled(): void
    {
        Config::set('env', 'dev');
        Config::set('devtools', [
            'enabled' => true,
            'address' => 'tcp://127.0.0.1:9567',
        ]);

        new Kernel([new DevToolsProvider()]);

        $this->assertTrue($this->getEventCollectorProperty('initialized'));
        $this->assertTrue($this->getEventCollectorProperty('enabled'));
        $this->assertInstanceOf(SocketEmitter::class, $this->getEventCollectorProperty('emitter'));
    }

    public function testProviderBootKeepsCollectorDisabledOutsideDevMode(): void
    {
        Config::set('env', 'prod');
        Config::set('devtools', true);

        new Kernel([new DevToolsProvider()]);

        $this->assertTrue($this->getEventCollectorProperty('initialized'));
        $this->assertFalse($this->getEventCollectorProperty('enabled'));
        $this->assertNull($this->getEventCollectorProperty('emitter'));
    }

    private function resetEventCollector(): void
    {
        $this->setEventCollectorProperty('initialized', false);
        $this->setEventCollectorProperty('enabled', false);
        $this->setEventCollectorProperty('emitter', null);
    }

    private function getEventCollectorProperty(string $property): mixed
    {
        $reflection = new \ReflectionClass(EventCollector::class);
        $prop = $reflection->getProperty($property);

        return $prop->getValue();
    }

    private function setEventCollectorProperty(string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass(EventCollector::class);
        $prop = $reflection->getProperty($property);
        $prop->setValue(null, $value);
    }
}
