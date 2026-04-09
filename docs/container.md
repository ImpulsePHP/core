# Conteneur et services

Le conteneur d'Impulse est `Impulse\Core\Container\ImpulseContainer`.

Il sait :

- enregistrer un service via une factory ;
- retourner des singletons ;
- auto-instancier des classes concrètes ;
- injecter les dépendances dans les constructeurs ;
- appeler une méthode en résolvant automatiquement ses dépendances.

## Accès via `App`

```php
use Impulse\Core\App;

App::boot();

$container = App::container();
$service = App::get(App\Service\UserService::class);
```

## Enregistrer un service

```php
use Impulse\Core\Container\ImpulseContainer;

$container->set(
    App\Service\UserService::class,
    fn (ImpulseContainer $c) => new App\Service\UserService(
        $c->get(App\Repository\UserRepository::class)
    )
);
```

Par défaut, `set()` enregistre un singleton.

## Auto-wiring

Le conteneur peut instancier une classe concrète sans définition explicite si ses dépendances sont résolvables.

```php
$mailer = $container->make(App\Service\Mailer::class);
```

### Règles de résolution

- les types objets non scalaires sont résolus automatiquement ;
- `ImpulseContainer` lui-même peut être injecté ;
- une dépendance déjà enregistrée via `set()` est réutilisée ;
- une dépendance non enregistrée est instanciée récursivement ;
- les paramètres scalaires doivent avoir une valeur par défaut ou être fournis explicitement.

## Appeler une méthode avec injection

```php
$container->call([App\Command\SyncUsers::class, 'run']);
```

Vous pouvez aussi fournir des paramètres nommés :

```php
$container->call(
    [App\Service\Importer::class, 'import'],
    ['path' => '/tmp/users.csv']
);
```

## Enregistrer un namespace

Le kernel enregistre déjà le namespace interne `Impulse\Core`.

Vous pouvez aussi enregistrer un namespace projet pour l'exposer comme services auto-résolvables :

```php
$container->registerNamespace('App\\Service', __DIR__ . '/../src/Service');
```

## Services core enregistrés

Le `CoreServiceProvider` enregistre notamment :

- `Impulse\Core\Contracts\EventDispatcherInterface`
- `Impulse\Core\Contracts\StateInterface`
- `Impulse\Core\Contracts\StoreInterface`
- `Impulse\Core\Contracts\ExceptionHandlerInterface`

## Bonnes pratiques

- utilisez le conteneur pour les services métier, pas pour les valeurs passagères ;
- préférez l'injection de dépendances au `new` dans vos providers et composants ;
- gardez les factories simples et sans effets de bord ;
- réservez `App::get()` aux points d'entrée et aux usages ponctuels.
