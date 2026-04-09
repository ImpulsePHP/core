# Architecture et cycle de vie

`impulsephp/core` fournit la base d'exécution d'une application Impulse :

- chargement de la configuration ;
- boot du kernel ;
- enregistrement des providers ;
- résolution des pages, composants et layouts ;
- rendu HTML ou dispatch AJAX ;
- collecte des styles, scripts, métriques et événements.

## Vue rapide

```text
App::boot()
  -> Impulse::boot()
  -> Config::load()
  -> ProviderManager / providers
  -> Kernel + ImpulseContainer

Request HTTP
  -> PageRouter::handle()
  -> matching de route
  -> middlewares globaux + page
  -> instanciation de la page
  -> rendu du layout
  -> HtmlResponse
```

## Bootstrap de l'application

Le point d'entrée applicatif utilise généralement `Impulse\Core\App`.

```php
use Impulse\Core\App;

require_once __DIR__ . '/vendor/autoload.php';

App::boot();
```

### Ce que fait `App::boot()`

- appelle `Impulse::boot()` ;
- recharge la configuration ;
- réinitialise le registre de routes provider ;
- prépare le renderer configuré ;
- instancie les providers listés dans `impulse.php` ;
- ajoute le `CoreServiceProvider` ;
- construit le `Kernel`.

## Rôles des classes principales

- `App` : façade d'accès au kernel et au conteneur.
- `Kernel` : enregistre et boot les providers.
- `Impulse` : boot interne du runtime et du renderer.
- `ImpulseContainer` : conteneur de services avec auto-wiring.
- `PageRouter` : résolution de page, middlewares, cache et rendu HTML.
- `AjaxDispatcher` : exécution des actions de composants lors des requêtes asynchrones.

## Organisation d'un projet Impulse

Conventions les plus courantes côté projet principal :

- `src/Page` : pages HTTP.
- `src/Component` : composants réutilisables.
- `src/Layout` : layouts.
- `src/Provider` : providers applicatifs ou de modules.
- `src/Renderer` : renderers personnalisés.
- `views` : templates si un moteur de vues est configuré.
- `impulse.php` : configuration principale.

## Types d'extension

Le package est conçu pour être étendu par :

- des pages annotées avec `#[PageProperty]` ;
- des composants rendus en HTML ;
- des layouts applicatifs ;
- des middlewares ;
- des providers ;
- des listeners d'événements ;
- des renderers personnalisés.

## Recommandation de lecture

Pour comprendre le package rapidement :

1. lisez `impulse-configuration.md` ;
2. lisez `pages_components.md` et `layouts.md` ;
3. lisez `http.md` pour les requêtes, réponses et middlewares ;
4. lisez `providers.md` si vous développez un module ou un package.
