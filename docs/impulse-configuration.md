# Configuration Impulse (fichier `impulse.php`)

Ce document décrit le fichier de configuration `impulse.php` qui doit se trouver à la racine d'un projet Impulse. Le fichier doit retourner un tableau PHP associatif (array) contenant les clés de configuration. Les commentaires expliquent en français à quoi sert chaque option.

Emplacement recommandé :

- ./impulse.php (à la racine du projet)

Format :

```php
<?php

declare(strict_types=1);

// Le fichier doit retourner un array.
return [
    // Nom de l'application (utile pour l'affichage ou logs). Facultatif.
    'app_name' => 'Mon projet Impulse',

    // Environnement d'exécution : 'prod' | 'dev' | 'test' — influence le comportement (gestion d'erreurs, cache...). Facultatif.
    'env' => 'dev',

    // Mode debug : true pour afficher plus d'informations en développement. Facultatif.
    'debug' => true,

    // Locale par défaut du projet (ex: 'fr', 'en'). Facultatif mais recommandé si vous utilisez la traduction.
    'locale' => 'fr',

    // Langues supportées (clé utilisée par le système de traduction : 'supported').
    // Note : dans le code la clé attendue est 'supported' (tableau de codes langue).
    'supported' => ['fr', 'en'],

    // Fuseau horaire. Facultatif.
    'timezone' => 'Europe/Paris',

    // Chemin relatif (depuis la racine du projet) vers le dossier public / assets.
    // Exemple : 'public' ou 'web'. Facultatif.
    'public_path' => 'public',

    // Configuration du moteur de template. Voir la commande `renderer:configure` qui écrit
    // automatiquement ces clés dans impulse.php.
    // - 'template_engine' : nom du renderer (ex: 'blade', 'twig', null pour aucun)
    // - 'template_path' : dossier contenant les vues (ex: 'views')
    'template_engine' => null, // 'blade', 'twig', 'mustache', etc. Utilisez null pour aucun.
    'template_path' => 'views',

    // Drapeaux ou options liées au cache des templates (si votre renderer les supporte)
    'view_cache' => [
        'enabled' => false,
        'path' => 'var/cache/views',
    ],

    // Espaces de noms personnalisés pour les composants utilisateur.
    // Par défaut Impulse ajoute 'App\\Component\\' automatiquement si absent.
    'component_namespaces' => [
        'App\\Component\\',
        // 'MonBundle\\Component\\',
    ],

    // Providers — classes qui enregistrent des services / composants / routes.
    // Exemple :
    'providers' => [
        // App\\Provider\\AppServiceProvider::class,
    ],

    // Configuration basique de la base de données (structure d'exemple). Les providers
    // ou modules (ex: database) peuvent attendre un tableau 'database' structuré.
    'database' => [
        'default' => 'sqlite',
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => 'var/database/database.sqlite',
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'db_name',
                'username' => 'db_user',
                'password' => 'secret',
                'charset' => 'utf8mb4',
            ],
        ],
    ],

    // Session / cookie (exemple minimal)
    'session' => [
        'cookie' => 'impulse_session',
        'lifetime' => 1200, // en secondes
    ],

    // Cache général (exemple)
    'cache' => [
        'driver' => 'file',
        'path' => 'var/cache',
    ],

    // Logs — configuration pour l'outil de logging interne et les DevTools
    // Note : le code lit notamment 'logs.enabled' pour activer la collecte des logs.
    'logs' => [
        'enabled' => true,
        'level' => 'debug', // debug|info|notice|warning|error
        'path' => 'var/log/impulse.log',
    ],

    // Clé secrète (ex : pour signer cookies / tokens) — facultatif mais recommandé.
    'secret_key' => 'change_me_to_a_random_secret',

    // Clé utilisée pour chiffrer les "state" protégés (voir State component).
    // Obligatoire si vous utilisez des states protégés : doit être une chaîne d'au moins 32 caractères
    // (ex: base64_encode(random_bytes(32))).
    'state_encryption_key' => base64_encode(random_bytes(32)),

    // Middlewares globaux — liste des classes middleware appliquées avant les pages.
    'middlewares' => [
        // App\Http\Middleware\ExampleMiddleware::class,
    ],

    // Layout par défaut (classe PHP complète). Utilisé si aucune page/layout n'est défini.
    'template_layout' => null,

    // Activation des outils de développement (DevTools). Utilisé conjointement avec 'env' === 'dev'.
    // Peut être un booléen simple ou un tableau plus complet.
    'devtools' => [
        'enabled' => false,
        'address' => 'tcp://127.0.0.1:9567',
    ],

    // CSS / assets fournis par des providers comme "story". Peut être :
    // - un tableau de chaînes (liens relatifs)
    // - un tableau d'objets ['path' => '...', 'base' => '...', 'inline' => bool]
    'css' => [],

    // Chemins pour le scanner 'story' (ex : pour retrouver des composants/story pages).
    'story' => [
        'paths' => [],
    ],

    // Options supplémentaires / personnalisées :
    // Vous pouvez ajouter n'importe quelle clé spécifique à vos providers ou packages.
];
```

Quelles options sont obligatoires pour démarrer à minima ?

- Strictement parlant, aucun champ n'est imposé : Impulse recherche automatiquement `impulse.php`
  et, s'il existe, le charge (voir `Impulse\Core\Support\Config::load`).
  Si le fichier est absent, Impulse utilisera des valeurs par défaut internes.

- Pour une configuration minimale pratique et éviter des comportements inattendus, il est
  recommandé d'ajouter au moins :
  - `template_path` (ex: 'views') — pour que la commande de configuration des renderers
    et le rendu de pages sachent où chercher les templates.
  - `template_engine` (peut être `null` pour démarrer sans moteur externe).

Exemples rapides :

- Minimal (démarrage rapide) :

```php
<?php
return [
    'template_engine' => null,
    'template_path' => 'views',
];
```

- Exemple plus complet (production) :

```php
<?php
return [
    'app_name' => 'MonSite',
    'env' => 'prod',
    'debug' => false,
    'locale' => 'fr',
    'template_engine' => 'blade',
    'template_path' => 'views',
    'view_cache' => [
        'enabled' => true,
        'path' => 'var/cache/views'
    ],
    'cache' => [
        'driver' => 'file',
        'path' => 'var/cache'
    ],
    'log' => [
        'level' => 'warning',
        'path' => 'var/log/impulse.log'
    ],
    'secret_key' => 'une_clef_secrete_longue_et_aléatoire',
];
```

Bonnes pratiques :

- Ne stockez pas de secrets en clair dans `impulse.php` dans un dépôt public. Préférez
  les variables d'environnement et un provider chargé depuis `bootstrap` ou un fichier
  local ignoré par Git.
- Utilisez `Config::set()` dans vos providers si vous avez besoin de modifier dynamiquement
  la configuration lors du bootstrap.
- `component_namespaces` permet d'ajouter d'autres espaces de noms où Impulse cherchera
  vos composants personnalisés.

Pour plus d'intégration automatisée, utilisez la commande `renderer:configure` (voir docs CLI)
qui vous aide à initialiser `template_engine` et `template_path` et peut ajouter le package
composer associé.

*** Fin de la documentation du fichier `impulse.php`.
