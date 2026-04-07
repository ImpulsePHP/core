# Conteneur et services

Le package expose un conteneur de services accessible via `Impulse\Core\App`.

- `App::boot()` : initialise le kernel et les providers.
- `App::container()` : retourne l'instance du conteneur.
- `App::get($id)` : récupération d'un service enregistré.

Exemple :

```php
use Impulse\Core\App;

App::boot();

$translator = App::get(Impulse\Translation\Contract\TranslatorInterface::class);
```

Les providers enregistrés dans la configuration (clé `providers`) sont instanciés lors du boot et peuvent lier des services dans le conteneur.

