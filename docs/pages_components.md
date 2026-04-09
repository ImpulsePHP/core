# Pages, composants et routage

Ce guide couvre la partie la plus visible du package :

- déclarer des pages ;
- déclarer des composants ;
- utiliser les states, actions, slots et assets ;
- comprendre comment les routes sont découvertes et générées.

## Pages

Une page est une classe qui étend `AbstractPage` et porte un `#[PageProperty]`.

```php
namespace App\Page;

use App\Layout\DefaultLayout;
use Impulse\Core\Attributes\PageProperty;
use Impulse\Core\Component\AbstractPage;

#[PageProperty(
    route: '/login',
    name: 'login',
    title: 'Connexion',
    layout: DefaultLayout::class
)]
final class LoginPage extends AbstractPage
{
    public function template(): string
    {
        return $this->view('pages.login');
    }
}
```

### API utile d'une page

- `getRouteParameters()` : paramètres de route capturés ;
- `getQuery()` : query string actuelle ;
- `layout()` : permet de choisir un layout dynamiquement ;
- `view()` : rend un template via le renderer configuré.

## Routes

Le `PageRouter` charge les fichiers `*Page.php` et lit leur `PageProperty`.

### Routes statiques

```php
#[PageProperty(route: '/dashboard', name: 'dashboard')]
```

### Routes dynamiques

```php
#[PageProperty(route: '/blog/[:slug]', name: 'blog.show')]
```

Le motif `[:slug]` est converti en paramètre capturable.

### Accéder aux paramètres

```php
$slug = $this->getRouteParameters()->get('slug');
```

### Générer une URL

```php
use Impulse\Core\Http\Router\PageRouter;

$router = PageRouter::instance() ?? new PageRouter();
$url = $router->generate('blog.show', ['slug' => 'hello-world']);
```

## Redirection par nom de page

La classe `Response` expose désormais une méthode dédiée :

```php
use Impulse\Core\Http\Response;

return Response::redirectToPage('login');
return Response::redirectToPage('blog.show', ['slug' => 'hello-world']);
```

Le nom utilisé est celui de `PageProperty(name: ...)`.

## Composants

Un composant étend `Impulse\Core\Component\AbstractComponent`.

```php
namespace App\Component;

use Impulse\Core\Component\AbstractComponent;

final class AlertComponent extends AbstractComponent
{
    public function setup(): void
    {
        $this->state('type', 'info', ['info', 'success', 'warning', 'error']);
        $this->state('message', '');
    }

    public function template(): string
    {
        return <<<HTML
            <div class="alert alert-{$this->type}">
                {$this->message}
            </div>
        HTML;
    }
}
```

## Comment un composant est trouvé dans un template

Le moteur analyse les namespaces listés dans `component_namespaces`.

### Nom de tag par défaut

Le tag HTML est dérivé du nom de classe en kebab-case, sans le suffixe `Component`.

- `AlertComponent` devient `<alert></alert>`
- `UserCardComponent` devient `<user-card></user-card>`

### Tag personnalisé

Vous pouvez définir un tag fixe :

```php
final class AlertComponent extends AbstractComponent
{
    public ?string $tagName = 'ui-alert';
}
```

## Props et states

Les attributs HTML transmis au composant sont convertis en props. Les noms en kebab-case sont transformés en camelCase.

```html
<user-card user-id="42" user-name="Guillaume"></user-card>
```

Le composant reçoit alors :

- `userId`
- `userName`

### Initialisation automatique des states scalaires

Après `setup()`, les valeurs scalaires passées dans les props sont automatiquement converties en states si aucun state n'existe encore sous ce nom.

Cela permet d'écrire :

```php
$this->state('title', 'Titre par defaut');
```

ou même d'utiliser directement un attribut scalaire reçu dans le template si cela suffit.

## Méthodes importantes de `AbstractComponent`

- `setup()` : configuration initiale du composant ;
- `boot()` : méthode optionnelle appelée via le conteneur avant `setup()` ;
- `template()` : HTML brut ou appel à `view()` ;
- `state()` : crée ou récupère un state ;
- `states()` : déclaration groupée de plusieurs states ;
- `watch()` : observe une modification de state ;
- `style()` : retourne du CSS ou SCSS compilé automatiquement ;
- `script()` : retourne du JavaScript inline ;
- `slot()` : retourne le slot principal ;
- `slot('name')` : retourne un slot nommé si le composant a été instancié avec ;
- `emit()` : met un événement dans la file de dispatch ;
- `disablePageCache()` : désactive le cache de page pour la requête courante ;
- `getRequest()` : récupère la requête courante ;
- `getNameCurrentRoute()` : récupère le nom de la route courante si disponible.

## States

Un state est représenté par `Impulse\Core\Component\State\State`.

### Déclarer un state simple

```php
$this->state('count', 0);
```

### Déclarer un state avec valeurs autorisées

```php
$this->state('status', 'draft', ['draft', 'published']);
```

### Déclarer plusieurs states

```php
$this->states([
    'count' => 0,
    'status' => ['draft', ['draft', 'published']],
]);
```

### Lire et écrire

```php
$this->count = 10;
$current = $this->count;
```

### Watchers

```php
$this->state('count', 0);

$this->watch($this->getComponentId() . '__count', function ($new, $old) {
    // réaction au changement
});
```

Le watcher s'attache à la clé interne du state. Pour un état local `count`, la clé stockée est préfixée par l'identifiant du composant.

### States protégés

Le quatrième argument de `state()` active le mode protégé prévu par l'objet `State`.

```php
$this->state('token', 'secret', null, true);
```

Le core contient la logique de chiffrement/déchiffrement associée à ce mode. Si vous l'utilisez, définissez `state_encryption_key` dans `impulse.php`.

## Exposer des states au front

Par défaut, les states ne sont pas injectés dans `data-states`.

Pour les exposer :

```php
public function shouldExposeStates(): bool
{
    return true;
}

public function exposedStates(): array
{
    return [
        'count' => $this->count,
    ];
}
```

## Slots de composants

Le slot principal d'un composant correspond à son contenu enfant.

```html
<alert>
    Bienvenue.
</alert>
```

Puis côté composant :

```php
return <<<HTML
    <div class="alert">
        {$this->slot()}
    </div>
HTML;
```

## Styles et scripts par composant

### CSS / SCSS inline

```php
public function style(): ?string
{
    return <<<SCSS
        .alert {
            color: white;
            background: #1f2937;
        }
    SCSS;
}
```

Le contenu est compilé par `scssphp`. Pour un composant classique, le style est scoppé automatiquement sur son wrapper.

### JavaScript inline

```php
public function script(): ?string
{
    return "console.log('component loaded');";
}
```

## Templates et renderers

Un composant peut :

- retourner directement du HTML ;
- appeler `$this->view('template.name', [...])` si un renderer est configuré.

Si `template()` retourne une chaîne vide et qu'un renderer est configuré, le composant peut aussi être rendu via `getTemplateName()` et `getViewData()`.

## Actions AJAX

Une méthode décorée avec `#[Action]` peut être appelée par le runtime AJAX.

```php
use Impulse\Core\Attributes\Action;

#[Action]
public function increment(): void
{
    $this->count++;
}
```

Hooks disponibles :

- `onBeforeAction(?string $method, array $args = [])`
- `onAfterAction()`

## Fragments de mise à jour

Le composant supporte un mode de rendu partiel autour de l'attribut `data-update`.

```html
<div data-update="counter@value">
    {$this->count}
</div>
```

Cette fonctionnalité est principalement utilisée par le moteur côté client pour mettre à jour des fragments ciblés.

## Composant `Router`

Le composant intégré `Impulse\Core\Component\BuiltIn\Router` rend un lien compatible avec la navigation client-side.

```html
<router name="login">Se connecter</router>
```

Il génère un lien avec `href` résolu depuis le nom de page et l'attribut `data-router`.

## Conseils pratiques

- gardez `setup()` pour l'état et `template()` pour le rendu ;
- utilisez `boot()` si vous avez besoin d'injection par le conteneur ;
- préférez des states simples et explicites ;
- utilisez `view()` si votre composant grossit ;
- réservez `data-update` et `shouldExposeStates()` aux besoins de mise à jour côté client.
