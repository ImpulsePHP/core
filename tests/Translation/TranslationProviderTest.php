<?php

namespace Impulse\Core\Tests\Translation;

use Impulse\Core\Bootstrap\Kernel;
use Impulse\Core\Support\Config;
use Impulse\Translation\Contract\TranslatorInterface;
use Impulse\Translation\TranslatorProvider;
use PHPUnit\Framework\TestCase;

class TranslationProviderTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        $this->assertTrue(
            class_exists(TranslatorProvider::class),
            'TranslatorProvider doit être chargé via tests/bootstrap.php'
        );
    }

    public function testTranslatorProviderRegistersTranslatorService(): void
    {
        $kernel = new Kernel([new TranslatorProvider()]);
        $translator = $kernel->getContainer()->get(TranslatorInterface::class);

        $this->assertInstanceOf(TranslatorInterface::class, $translator);
    }

    public function testTranslatorUsesConfiguredLocale(): void
    {
        Config::set('locale', 'fr');
        Config::set('supported', ['fr', 'en']);

        $kernel = new Kernel([new TranslatorProvider()]);
        $translator = $kernel->getContainer()->get(TranslatorInterface::class);

        $this->assertSame('fr', $translator->getLocale());
    }
}

