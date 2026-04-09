# ImpulsePHP Core

`impulsephp/core` est le socle du framework ImpulsePHP. Il fournit :

- le bootstrap de l'application ;
- le conteneur de services ;
- les providers ;
- la couche HTTP ;
- les pages, composants et layouts ;
- les renderers ;
- le cache HTML ;
- les collecteurs d'assets, logs et profils.

## Installation

```bash
composer require impulsephp/core
```

## Configuration minimale

```php
<?php

return [
    'template_engine' => null,
    'template_path' => 'views',
    'providers' => [],
    'middlewares' => [],
    'locale' => 'fr',
    'supported' => ['fr', 'en'],
    'cache' => [
        'enabled' => true,
        'ttl' => 600,
    ],
    'devtools' => false,
];
```

## Démarrage rapide

```php
use Impulse\Core\App;

require_once __DIR__ . '/vendor/autoload.php';

App::boot();
```

## Exemple de page

```php
namespace App\Page;

use App\Layout\DefaultLayout;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(
    route: '/',
    name: 'home',
    title: 'Accueil',
    layout: DefaultLayout::class
)]
final class HomePage extends AbstractPage
{
    public function template(): string
    {
        return <<<HTML
            <h1>Accueil</h1>
        HTML;
    }
}
```

## Exemple de composant

```php
namespace App\Component;

use Impulse\Core\Attributes\Action;
use Impulse\Core\Component\AbstractComponent;

final class CounterComponent extends AbstractComponent
{
    public function setup(): void
    {
        $this->state('count', 0);
    }

    #[Action]
    public function increment(): void
    {
        $this->count++;
    }

    public function template(): string
    {
        return <<<HTML
            <button>{$this->count}</button>
        HTML;
    }
}
```

## HTTP

### Redirection simple

```php
use Impulse\Core\Http\Response;

return Response::redirect('/login');
```

### Redirection par nom de page

```php
return Response::redirectToPage('login');
```

### Message flash

```php
return Response::redirectToPage('login')
    ->withFlash('registered', '1');
```

Puis sur la requête suivante :

```php
$registered = $request->getFlash('registered');
```

## DevTools provider

Pour initialiser les DevTools dans un projet :

```php
return [
    'env' => 'dev',
    'devtools' => [
        'enabled' => true,
        'address' => 'tcp://127.0.0.1:9567',
    ],
    'providers' => [
        Impulse\Core\Provider\DevToolsProvider::class,
    ],
];
```

## Documentation détaillée

La documentation détaillée est disponible dans [`docs/`](./docs/README.md).

Principaux guides :

- [Architecture et cycle de vie](./docs/architecture.md)
- [Configuration](./docs/impulse-configuration.md)
- [Pages, composants et routage](./docs/pages_components.md)
- [Layouts](./docs/layouts.md)
- [HTTP, Request, Response et middleware](./docs/http.md)
- [Conteneur et providers](./docs/container.md)
- [Providers](./docs/providers.md)
- [Renderers](./docs/renderers.md)
- [Événements, store et DevTools](./docs/events-devtools.md)
- [Cache et performances](./docs/cache.md)
- [CLI `bin/impulse`](./docs/bin-impulse.md)

## Tests

```bash
composer test
```

## Licence

MIT
