<?php

namespace Impulse\Core\Tests\Support;

use Impulse\Core\Support\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLoadReadsPhpConfig(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'impulse');
        file_put_contents($tmp, "<?php return ['template_engine' => 'twig', 'component_namespaces' => ['Custom\\\\Component\\\\']];");

        Config::reset();
        Config::load($tmp);

        $this->assertSame('twig', Config::get('template_engine'));
        $namespaces = Config::get('component_namespaces');
        $this->assertContains('Custom\\Component\\', $namespaces);

        unlink($tmp);
    }

    public function testSaveWritesPhpConfig(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'impulse');

        Config::reset();
        Config::load($tmp);
        Config::set('template_engine', 'blade');
        Config::set('component_namespaces', ['Foo\\\\Component\\\\']);
        Config::save($tmp);

        $data = require $tmp;

        $this->assertSame('blade', $data['template_engine']);
        $this->assertSame(['Foo\\Component\\'], $data['component_namespaces']);

        unlink($tmp);
    }
}
