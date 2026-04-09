# Documentation ImpulsePHP Core

Cette documentation couvre les surfaces publiques du package `impulsephp/core` telles qu'elles existent dans le code source actuel.

## Vue d'ensemble

- [Architecture et cycle de vie](architecture.md)
- [Configuration `impulse.php`](impulse-configuration.md)
- [Conteneur et services](container.md)
- [Providers](providers.md)

## Développement d'interface

- [Attributs PHP](attributes.md)
- [Pages, composants et routage](pages_components.md)
- [Layouts](layouts.md)
- [HTTP, Request, Response et middleware](http.md)
- [Renderers et vues](renderers.md)

## Runtime et outillage

- [Événements, store localStorage et DevTools](events-devtools.md)
- [Cache, profiler et performances](cache.md)
- [CLI `bin/impulse`](bin-impulse.md)

## Conseils de lecture

- Commencez par `architecture.md` si vous découvrez le package.
- Lisez ensuite `pages_components.md`, `layouts.md` et `http.md` pour développer une application.
- Consultez `providers.md`, `container.md` et `renderers.md` si vous étendez le framework.
- Utilisez `events-devtools.md` et `cache.md` pour les besoins avancés et le diagnostic.
