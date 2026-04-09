# Renderers et vues

Les renderers permettent à `view()` de transformer un nom de template en HTML.

## Principe

Le renderer actif est choisi via `template_engine` dans `impulse.php`.

```php
return [
    'template_engine' => 'blade',
    'template_path' => 'views',
];
```

Le moteur est ensuite instancié par `OptimizedFactory`.

## Renderers fournis

### `html`

- classe : `Impulse\Core\Renderer\HtmlRenderer`
- attribut : `#[Renderer(name: 'html')]`
- comportement : retourne simplement la chaîne fournie

Ce renderer est surtout utile pour du HTML direct.

### `blade`

- classe : `Impulse\Core\Renderer\BladeRenderer`
- package : `illuminate/view`

Exemple :

```php
return [
    'template_engine' => 'blade',
    'template_path' => 'views',
];
```

Puis dans un composant :

```php
return $this->view('pages.login', [
    'title' => 'Connexion',
]);
```

### `twig`

- classe : `Impulse\Core\Renderer\TwigRenderer`
- package : `twig/twig`

Le renderer ajoute automatiquement l'extension `.twig` si nécessaire.

## Utiliser `view()`

Dans un composant, une page ou un layout :

```php
public function template(): string
{
    return $this->view('pages.dashboard', [
        'user' => $this->user,
    ]);
}
```

Si aucun tableau n'est fourni, `view()` utilise `getViewData()`.

## Créer un renderer personnalisé

Implémentez `TemplateRendererInterface` et déclarez l'attribut `#[Renderer]`.

```php
namespace App\Renderer;

use Impulse\Core\Attributes\Renderer;
use Impulse\Core\Contracts\TemplateRendererInterface;

#[Renderer(name: 'mustache', bundle: 'mustache/mustache')]
final class MustacheRenderer implements TemplateRendererInterface
{
    public function __construct(string $viewsPath = '')
    {
        // ...
    }

    public function render(string $template, array $data = []): string
    {
        return '';
    }
}
```

Ensuite :

```php
return [
    'template_engine' => 'mustache',
    'template_path' => 'views',
];
```

## Résolution du renderer

La factory :

- inspecte `src/Renderer` du projet ;
- inspecte les renderers du core ;
- cherche une classe portant `#[Renderer(name: ...)]`.

## Caches de renderer

Les renderers fournis écrivent leurs caches dans :

- Blade : `var/storage/cache/blade`
- Twig : `var/storage/cache/twig`

## Bonnes pratiques

- gardez vos templates dans le dossier configuré par `template_path` ;
- utilisez des noms de vues stables et explicites ;
- préférez `view()` pour les composants riches et les pages longues ;
- gardez le HTML direct pour les composants très simples.
