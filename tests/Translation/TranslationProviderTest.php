<?php

namespace Impulse\Core\Tests\Translation;

use Impulse\Core\App;
use Impulse\Core\Kernel\Impulse;
use Impulse\Core\Support\Config;
use Impulse\Core\Translation\TranslationProvider;
use PHPUnit\Framework\TestCase;

class TranslationProviderTest extends TestCase
{
    protected function setUp(): void
    {
        Config::reset();
        Impulse::boot();
    }

    public function testLocaleFromPrefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/fr/contact';
        $_SERVER['QUERY_STRING'] = '';
        Config::set('locale', 'en');
        Config::set('supported', ['fr', 'en']);

        $provider = new TranslationProvider();
        Impulse::registerProvider($provider);
        Impulse::bootProviders();

        $this->assertSame('fr', App::getLocale());
        $this->assertSame('/contact', $_SERVER['REQUEST_URI']);
    }

    public function testDefaultLocaleWhenNoPrefix(): void
    {
        $_SERVER['REQUEST_URI'] = '/about';
        $_SERVER['QUERY_STRING'] = '';
        Config::set('locale', 'en');
        Config::set('supported', ['fr', 'en']);

        $provider = new TranslationProvider();
        Impulse::registerProvider($provider);
        Impulse::bootProviders();

        $this->assertSame('en', App::getLocale());
        $this->assertSame('/about', $_SERVER['REQUEST_URI']);
    }

    public function testGetCurrentPathHelper(): void
    {
        $_SERVER['REQUEST_URI'] = '/fr/accueil';
        $_SERVER['QUERY_STRING'] = '';
        Config::set('locale', 'en');
        Config::set('supported', ['fr', 'en']);

        $provider = new TranslationProvider();
        Impulse::registerProvider($provider);
        Impulse::bootProviders();

        $this->assertSame('/accueil', getCurrentPath());
        $this->assertSame('/en/accueil', getCurrentPath('en'));
    }
}

