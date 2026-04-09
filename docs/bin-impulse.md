# CLI `bin/impulse`

Le package expose des commandes Symfony Console destinées au scaffolding et à la configuration rapide d'un projet.

## Exécution

Depuis la racine du projet :

```bash
php ./bin/impulse list
php ./bin/impulse <commande> --help
```

## Commandes principales

### `renderer:configure`

Alias :

- `r:config`
- `renderer:setup`
- `renderer:config`

Cette commande :

- détecte les renderers disponibles ;
- demande le moteur à utiliser ;
- demande le chemin des vues ;
- met à jour `impulse.php`.

## `make:component`

Alias :

- `m:component`
- `c:make`

Crée un squelette de composant dans `src/Component`.

Exemple :

```bash
php ./bin/impulse make:component
```

## `make:page`

Alias :

- `m:page`
- `p:make`

Crée un squelette de page dans `src/Page`.

Exemple :

```bash
php ./bin/impulse make:page
```

## `make:renderer`

Alias :

- `r:new`
- `r:make`

Crée un squelette de renderer dans `src/Renderer`.

Exemple :

```bash
php ./bin/impulse make:renderer
```

## Conseils

- exécutez toujours ces commandes à la racine du projet ;
- relisez les fichiers générés, ce sont des points de départ ;
- utilisez `renderer:configure` avant de vous reposer sur `view()`.

## Étendre la CLI

Le binaire étant basé sur Symfony Console, vous pouvez y enregistrer vos propres commandes :

```php
$app = new \Symfony\Component\Console\Application('Impulse CLI', '1.0.0');
$app->add(new \App\Console\SyncUsersCommand());
$app->run();
```
