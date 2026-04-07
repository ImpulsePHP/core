# ImpulsePHP Core

`impulsephp/core` est le socle du framework ImpulsePHP. Il fournit le bootstrap de l’application, le conteneur de services, les providers, la couche HTTP, les composants, les pages, les layouts et les outils de support utilisés par les autres packages de l’écosystème.

## Ce que fait le package

- initialise l’application via `Impulse\Core\App` ;
- charge la configuration et les providers ;
- expose le conteneur `ImpulseContainer` ;
- fournit les briques HTTP, composants, pages et layouts ;
- met à disposition des utilitaires comme le logger, le profiler et les DevTools.

## Prérequis

- PHP 8.2 ou supérieur ;
- extensions `dom`, `libxml` et `openssl`.

## Installation

```bash
composer require impulsephp/core
```

## Configuration minimale

Le package fournit une configuration de base dans `impulse.php` :

```php
<?php

return [
    'template_engine' => '',
    'template_path' => 'views',
    'middlewares' => [],
    'providers' => [],
    'locale' => 'fr',
    'supported' => ['fr', 'en', 'de'],
    'cache' => [
        'enabled' => true,
        'ttl' => 600,
    ],
    'devtools' => false,
];
```

Ajoutez ensuite vos providers métier dans la clé `providers`.

## Exemple d’usage complet

L’exemple ci-dessous montre un bootstrap minimal avec quelques providers courants.

```php
use Impulse\Core\App;

require_once __DIR__ . '/vendor/autoload.php';

App::boot();

$container = App::container();
$translator = App::get(Impulse\Translation\Contract\TranslatorInterface::class);
```

Exemple de configuration associée :

```php
return [
    'providers' => [
        Impulse\Translation\TranslatorProvider::class,
        Impulse\Validator\ValidatorProvider::class,
        Impulse\UI\UIProvider::class,
    ],
    'locale' => 'fr',
    'supported' => ['fr', 'en'],
];
```

## Utilisation du conteneur

### Récupérer un service

```php
use Impulse\Core\App;

App::boot();

$service = App::get(SomeService::class);
```

### Construire un kernel manuellement

```php
use Impulse\Core\Bootstrap\CoreServiceProvider;
use Impulse\Core\Bootstrap\Kernel;

$kernel = new Kernel([
    new CoreServiceProvider(),
]);

$kernel->boot();
$container = $kernel->getContainer();
```

## Pages, composants et routage

Les pages sont généralement déclarées avec l’attribut `#[PageProperty]`, qui définit notamment la route, le nom de la page, le titre et le layout utilisé.

Le composant `<router>` permet de générer des liens compatibles avec la navigation AJAX du moteur JavaScript Impulse.

Exemple de slot vers un layout :

```html
<slot-layout name="title">Titre de la page</slot-layout>
```

## HTTP, localStorage et réponse cliente

Le cœur du framework expose une couche HTTP légère sous `Impulse\Core\Http`. Il inclut aussi un pont avec le `localStorage` du navigateur afin de permettre des synchronisations entre le rendu serveur et le client lorsque le moteur JavaScript est présent.

## Journalisation et DevTools

Le logger intégré peut être utilisé sans configuration complexe :

```php
use Impulse\Core\Support\Logger;

Logger::info('Application démarrée', ['route' => '/dashboard']);
```

En activant `devtools` dans la configuration, certains événements de framework peuvent être diffusés à l’interface de développement.

## Documentation

La documentation détaillée du package est disponible dans le dossier `docs/`.

- [Guides et références (Table des matières)](./docs/README.md)

## Aller plus loin

`impulsephp/core` sert de fondation aux packages :

- `impulsephp/auth`
- `impulsephp/database`
- `impulsephp/translation`
- `impulsephp/validator`
- `impulsephp/story`
- `impulsephp/ui`

## Tests

```bash
composer test
```

## Licence

MIT
