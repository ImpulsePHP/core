# Cache, profiler et performances

Le core contient un cache HTML simple pour les pages, plus quelques mécanismes de collecte utiles pour le diagnostic.

## Cache HTML des pages

Le cache est géré par `Impulse\Core\Cache\PageCacheManager`.

## Activation

```php
return [
    'cache' => [
        'enabled' => true,
        'ttl' => 600,
    ],
];
```

## Driver utilisé

Par défaut, le cache de page utilise `FileCache` dans :

```text
var/storage/cache/page
```

## Quand une page est cacheable

Une page est mise en cache uniquement si :

- le cache global est activé ;
- la requête est en `GET` ;
- le cache n'a pas été désactivé dynamiquement ;
- `PageProperty(cache: false)` n'est pas défini ;
- la query string ne contient pas `action` ni `update`.

## Désactiver le cache

### Pour une page précise

```php
#[PageProperty(
    route: '/account',
    name: 'account',
    cache: false
)]
```

### Dynamiquement depuis un composant

```php
$this->disablePageCache();
```

## Clé de cache

La clé est calculée à partir de :

- `Request::getPath()`
- la query string
- la locale configurée

## Profiler

`Impulse\Core\Support\Profiler` mesure :

- la durée d'exécution de segments nommés ;
- l'utilisation mémoire ;
- les vues rendues.

### API

- `start(string $name)`
- `stop(string $name)`
- `getStats()`
- `getViews()`
- `flush()`

Le profiler n'est actif que si `env` vaut `dev`.

## Collecteurs d'assets

Trois collecteurs structurent les assets avant assemblage du HTML final :

- `HeadCollector`
- `StyleCollector`
- `ScriptCollector`

### `HeadCollector`

Permet d'ajouter des balises dans `<head>` avec priorité.

### `StyleCollector`

Permet :

- d'ajouter des feuilles CSS ;
- d'ajouter du CSS ou SCSS inline ;
- de compiler le SCSS via `scssphp`.

### `ScriptCollector`

Permet :

- d'ajouter un fichier JavaScript ;
- d'ajouter du code inline.

## Conseils

- laissez le cache activé pour les pages publiques en lecture seule ;
- désactivez-le pour les pages dépendantes d'un état utilisateur volatile ;
- utilisez le profiler en `dev` pour comprendre les temps de rendu ;
- centralisez les assets vraiment globaux dans le layout.
