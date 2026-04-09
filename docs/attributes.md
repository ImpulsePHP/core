# Attributs PHP

Le package s'appuie sur quelques attributs PHP pour déclarer du comportement sans configuration impérative.

## `#[PageProperty(...)]`

Namespace :

```php
use Impulse\Core\Attributes\PageProperty;
```

Cet attribut s'applique à une classe qui étend `Impulse\Core\Component\AbstractPage`.

```php
#[PageProperty(
    route: '/login',
    name: 'login',
    title: 'Connexion',
    layout: App\Layout\DefaultLayout::class,
    auth: false,
    roles: [],
    middlewares: [App\Http\Middleware\GuestOnlyMiddleware::class],
    cache: false,
    priority: 10
)]
final class LoginPage extends AbstractPage
{
    public function template(): string
    {
        return $this->view('pages.login');
    }
}
```

### Paramètres

- `route` : route HTTP, avec support des paramètres au format `[:name]`.
- `name` : nom logique de la page, utilisé notamment par `PageRouter::generate()` et `Response::redirectToPage()`.
- `title` : titre HTML injecté dans `<title>`.
- `layout` : classe de layout à utiliser.
- `auth` : métadonnée disponible pour vos extensions ou middlewares.
- `roles` : métadonnée disponible pour vos extensions ou middlewares.
- `middlewares` : middlewares ajoutés uniquement à cette page.
- `cache` : `false` désactive le cache HTML pour cette page.
- `priority` : priorité de tri des routes.

### Paramètres internes

- `class` et `file` sont remplis par le routeur lors du chargement.

## `#[LayoutProperty(...)]`

Namespace :

```php
use Impulse\Core\Attributes\LayoutProperty;
```

Cet attribut s'applique à une classe qui étend `Impulse\Core\Component\AbstractLayout`.

```php
#[LayoutProperty(
    titlePrefix: 'Mon application',
    titleSuffix: 'ImpulsePHP'
)]
final class DefaultLayout extends AbstractLayout
{
    public function template(): string
    {
        return <<<HTML
            <main>
                {$this->slot()}
            </main>
        HTML;
    }
}
```

### Effet

- Le routeur lit d'abord `LayoutProperty`.
- Si l'attribut n'est pas présent, il essaye `titlePrefix()` et `titleSuffix()` sur l'instance du layout.
- Le titre final est composé à partir de `prefix`, `page title`, `suffix`.

## `#[Action]`

Namespace :

```php
use Impulse\Core\Attributes\Action;
```

Marque une méthode publique de composant comme appelable par le dispatcher AJAX.

```php
#[Action]
public function save(): void
{
    // ...
}
```

### Règles

- la méthode doit être publique ;
- les méthodes magiques ne sont pas appelées ;
- le dispatcher peut injecter l'argument `value` envoyé par le client si nécessaire.

## `#[Renderer(...)]`

Namespace :

```php
use Impulse\Core\Attributes\Renderer;
```

Cet attribut déclare un moteur de rendu utilisable par la factory.

```php
#[Renderer(
    name: 'twig',
    bundle: 'twig/twig'
)]
final class TwigRenderer implements TemplateRendererInterface
{
    // ...
}
```

### Paramètres

- `name` : nom logique du renderer, utilisé par `template_engine`.
- `bundle` : package Composer attendu ou conseillé.
