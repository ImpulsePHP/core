# HTTP, Request, Response et middleware

La couche HTTP d'Impulse repose sur `Request`, `Response`, `PageRouter`, `ExceptionHandler` et `MiddlewareDispatcher`.

## `Request`

`Impulse\Core\Http\Request` encapsule la méthode, l'URI, les paramètres de query, les données POST et les variables serveur.

### Créer une requête

```php
use Impulse\Core\Http\Request;

$request = Request::createFromGlobals();
```

### API principale

- `getMethod()`
- `getUri()`
- `getPath()`
- `query()`
- `request()`
- `server()`
- `isAjax()`
- `expectsJson()`
- `isJson()`

### Lire la query string

```php
$registered = $request->query()->get('registered');
```

### Important

Modifier `query()` ne modifie que l'objet `Request` courant.

```php
$request->query()->set('registered', '1');
```

Cette opération ne survit pas à une redirection HTTP. Après un `302`, le navigateur émet une nouvelle requête, donc il faut soit :

- mettre la donnée dans l'URL ;
- utiliser la session ;
- utiliser un message flash.

## Messages flash

Le core fournit maintenant un mécanisme de flash basé sur la session.

### Écrire un flash

```php
$request->flash('registered', '1');
```

### Lire un flash

```php
$registered = $request->getFlash('registered');
```

### API disponible

- `flash(string $key, mixed $value)`
- `hasFlash(string $key)`
- `getFlash(string $key, mixed $default = null)` : lit et consomme ;
- `peekFlash(string $key, mixed $default = null)` : lit sans consommer ;
- `allFlashes(bool $clear = true)` : retourne tous les flashs.

## `Response`

`Impulse\Core\Http\Response` représente la réponse HTTP.

### Créer une réponse HTML

```php
return Response::html('<h1>Hello</h1>');
```

### Réponse JSON

```php
return Response::json(['ok' => true], 201);
```

### Redirection brute

```php
return Response::redirect('/login');
```

### Redirection par nom de page

```php
return Response::redirectToPage('login');
return Response::redirectToPage('blog.show', ['slug' => 'hello-world']);
```

### Chaîner un flash à une redirection

```php
return Response::redirectToPage('login')
    ->withFlash('registered', '1');
```

### Réponse vide

```php
return Response::noContent();
```

## Cas d'usage recommandé pour notification après redirection

```php
public function register(Request $request): Response
{
    // ...

    return Response::redirectToPage('login')
        ->withFlash('registered', '1');
}
```

Puis sur la page cible :

```php
$registered = $request->getFlash('registered');

if ($registered === '1') {
    // afficher la notification
}
```

## `PageRouter`

Le `PageRouter` :

- charge les pages ;
- résout la route correspondante ;
- exécute les middlewares ;
- rend la page et le layout ;
- applique le cache HTML ;
- délègue les erreurs à `ExceptionHandler`.

### Générer une URL

```php
$router = PageRouter::instance() ?? new PageRouter();
$url = $router->generate('dashboard');
```

### Résoudre le nom d'une route

```php
$meta = $router->findComponentForRoute('/login');
```

## Middleware

Un middleware implémente :

```php
use Impulse\Core\Contracts\MiddlewareInterface;
use Impulse\Core\Http\Request;
use Impulse\Core\Http\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirectToPage('login');
        }

        return $next($request);
    }
}
```

### Où déclarer les middlewares

Globalement dans `impulse.php` :

```php
return [
    'middlewares' => [
        App\Http\Middleware\AuthMiddleware::class,
    ],
];
```

Ou localement sur une page :

```php
#[PageProperty(
    route: '/account',
    name: 'account',
    middlewares: [App\Http\Middleware\AuthMiddleware::class]
)]
```

Les middlewares globaux et de page sont concaténés par le routeur.

## Gestion d'erreurs

`ExceptionHandler` fonctionne différemment selon l'environnement.

### En `dev`

- affiche le message ;
- affiche le fichier, la ligne et la stack trace.

### En `prod`

- tente de résoudre une page d'erreur personnalisée ;
- sinon renvoie une page HTML générique.

### Pages d'erreur personnalisées

Le handler cherche notamment :

- `App\Component\Errors\Error404Component`
- `App\Component\Error404Component`

et les équivalents pour les autres statuts.

## Réponses AJAX

Le dispatcher AJAX utilise aussi `Request` et `Response`, mais produit majoritairement du JSON.

Pour la logique applicative, retenez surtout :

- `#[Action]` rend une méthode appelable ;
- les states peuvent être renvoyés au client ;
- les événements émis sont flushés après action.
