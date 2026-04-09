# Événements, store localStorage et DevTools

Ce document couvre trois briques runtime souvent utilisées ensemble :

- les événements ;
- le store basé sur le `localStorage` du navigateur ;
- les outils de diagnostic.

## Événements

Le système d'événements repose sur :

- `Impulse\Core\Event\Event`
- `Impulse\Core\Event\EventDispatcher`
- `Impulse\Core\Contracts\ListenerInterface`

## Émettre un événement depuis un composant

```php
$this->emit('user.saved', [
    'id' => 42,
]);
```

La méthode `emit()` ajoute l'événement à la file du dispatcher.

## Dispatcher

```php
use Impulse\Core\Event\EventDispatcher;

$dispatcher = EventDispatcher::getInstance();
$dispatcher->addListener('user.saved', new App\Listener\UserSavedListener());
```

## Listener

```php
use Impulse\Core\Contracts\EventInterface;
use Impulse\Core\Contracts\ListenerInterface;

final class UserSavedListener implements ListenerInterface
{
    public function handle(EventInterface $event): void
    {
        $payload = $event->payload();
    }
}
```

## Remarque importante

Le dispatcher distingue :

- `dispatch()` : appelle immédiatement les listeners puis stocke l'événement ;
- `queue()` : empile l'événement sans l'exécuter immédiatement.

La méthode `AbstractComponent::emit()` utilise `queue()`.

## Store et `localStorage`

Le core propose un pont entre le serveur et le `localStorage` client via :

- `Impulse\Core\Support\LocalStorage`
- `Impulse\Core\Component\Store\Store`
- `Impulse\Core\Component\Store\LocalStorageStoreInstance`

## Récupérer un store

```php
use Impulse\Core\Component\Store\Store;

$preferences = Store::get('preferences');

$theme = $preferences->get('theme', 'light');
$preferences->set('theme', 'dark');
```

### API du store

- `get(string $key, mixed $default = null)`
- `set(string $key, mixed $value)`
- `has(string $key)`
- `all()`

## Synchronisation

Le runtime peut lire des données `_local_storage` dans :

- `$_POST`
- le corps JSON de la requête

Ces données sont ensuite synchronisées avec la session serveur.

## DevTools

Le package inclut une collecte d'événements internes via `DevToolsRegistry`.

### Types d'informations collectées

Selon le contexte et l'environnement :

- routes résolues ;
- requêtes HTTP ;
- logs ;
- exceptions ;
- statistiques de profiling ;
- informations de vues ;
- événements custom.

## Logger

```php
use Impulse\Core\Support\Logger;

Logger::info('Utilisateur connecté', ['id' => 42]);
Logger::error('Échec API', ['service' => 'billing']);
```

## Profiler

Le profiler n'est actif qu'en environnement `dev`.

```php
use Impulse\Core\Support\Profiler;

Profiler::start('import');
Profiler::stop('import');
```

Les renderers et le routeur utilisent déjà le profiler en interne.

## Quand utiliser ces briques

- utilisez `emit()` pour notifier d'autres composants ou le runtime ;
- utilisez `Store::get()` pour des données client persistantes simples ;
- utilisez `Logger` et `Profiler` pour diagnostiquer le comportement d'un module ;
- utilisez `DevToolsRegistry` si vous développez un outillage autour du core.
