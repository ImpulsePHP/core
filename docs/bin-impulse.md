# Utilisation de la commande CLI `./bin/impulse`

Ce document explique comment utiliser la commande CLI fournie dans un projet Impulse (fichier `bin/impulse`).

Exécutable :

- Le fichier `bin/impulse` (ex. : fourni dans un projet) est un exécutable Symfony Console.
- Pour l'exécuter depuis la racine du projet :

```bash
php ./bin/impulse <commande>
# ou si le fichier est exécutable :
./bin/impulse <commande>
```

Options générales (Symfony Console) :

- `list` : liste les commandes disponibles
- `--help` : obtenir l'aide d'une commande (`./bin/impulse <commande> --help`)

Commandes fournies par défaut (implémentées dans Core) :

- `renderer:configure` (alias : `r:config`, `renderer:setup`, `renderer:config`)
  - Description : initialise le projet avec un moteur de template.
  - Actions :
    - Découvre les renderers disponibles (dans `src/Renderer` et via l'autoload composer).
    - Demande quel moteur utiliser et le chemin des templates (par défaut `views`).
    - Écrit (ou met à jour) le fichier `impulse.php` avec `template_engine` et `template_path`.
    - Propose d'ajouter le package Composer du renderer choisi si nécessaire.
  - Exemple d'utilisation :

```bash
php ./bin/impulse renderer:configure
```

- `make:renderer` (alias : `r:new`, `r:make`)
  - Description : génère une classe de renderer personnalisée dans `src/Renderer`.
  - Interaction : pose le nom du renderer et crée un squelette de classe implémentant
    `TemplateRendererInterface` avec l'attribut `#[Renderer(...)]`.
  - Exemple :

```bash
php ./bin/impulse make:renderer
# puis saisissez : MyAwesomeRenderer
```

- `make:component` (alias : `m:component`, `c:make`)
  - Description : crée un composant (classe) dans `src/Component`.
  - Interaction : demande le nom du composant (ex: `NavbarComponent`) et crée le fichier.
  - Exemple :

```bash
php ./bin/impulse make:component
# puis saisissez : NavbarComponent
```

- `make:page` (alias : `m:page`, `p:make`)
  - Description : crée une page dans `src/Page` avec un attribut `PageProperty`.
  - Interaction : demande le nom de la page et l'URL (route), puis crée le squelette de classe.
  - Exemple :

```bash
php ./bin/impulse make:page
# puis saisissez : ContactPage
# puis saisissez l'URL : /contact
```

Conseils d'utilisation :

- Pour voir la liste complète et l'aide détaillée d'une commande :

```bash
php ./bin/impulse list
php ./bin/impulse make:component --help
```

- Exécutez toujours les commandes depuis la racine du projet (le script attend `vendor/autoload.php` à la racine).

- Si la commande `renderer:configure` propose d'installer un package Composer, elle lancera
  `composer require <package>` en utilisant le binaire `composer` disponible sur le système.

Personnaliser les commandes CLI pour votre projet :

- Le fichier `bin/impulse` est le point d'entrée : vous pouvez y ajouter des commandes
  supplémentaires en important et en enregistrant vos classes de commande :

```php
$app = new \Symfony\Component\Console\Application('Impulse CLI', '1.0.0');
$app->add(new \App\Console\MyCustomCommand());
$app->run();
```

- Alternativement, créez vos commandes dans un provider qui les enregistre automatiquement
  à l'initialisation si vous avez un mécanisme d'enregistrement centralisé.

Dépannage :

- Erreur « composer.json introuvable » lors de `renderer:configure` : exécutez la commande
  depuis la racine du projet où se trouve `composer.json`.
- Erreurs de permission pour `./bin/impulse` : rendez le fichier exécutable :

```bash
chmod +x ./bin/impulse
```

Résumé rapide :

- `./bin/impulse` est une interface simple pour les helpers de développement (scaffolding)
  et pour initialiser certains aspects du projet (renderer).
- Utilisez `renderer:configure` pour initialiser la couche de templates et `make:*` pour
  générer rapidement la structure de classes (components/pages/renderers).

