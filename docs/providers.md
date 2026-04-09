# Providers

Les providers servent à enregistrer des services, des namespaces de composants et des routes issues d'un module ou d'un package.

## Interface minimale

Un provider implémente `Impulse\Core\Contracts\ServiceProviderInterface` :

```php
interface ServiceProviderInterface
{
    public function register(ImpulseContainer $container): void;
    public function boot(ImpulseContainer $container): void;
}
```

Dans la pratique, il est recommandé d'étendre `Impulse\Core\Provider\AbstractProvider`.

## Exemple simple

```php
namespace App\Provider;

use App\Service\DashboardService;
use Impulse\Core\Container\ImpulseContainer;
use Impulse\Core\Provider\AbstractProvider;

final class AppServiceProvider extends AbstractProvider
{
    protected function registerServices(ImpulseContainer $container): void
    {
        $container->set(DashboardService::class, fn () => new DashboardService());
    }

    protected function onBoot(ImpulseContainer $container): void
    {
        // logique optionnelle de boot
    }
}
```

Puis dans `impulse.php` :

```php
return [
    'providers' => [
        App\Provider\AppServiceProvider::class,
    ],
];
```

## Auto-enregistrement des routes de pages

Si votre provider implémente `HasComponentRoutesInterface`, `AbstractProvider` charge automatiquement les pages trouvées dans le dossier retourné.

```php
use Impulse\Core\Contracts\HasComponentRoutesInterface;

final class BlogProvider extends AbstractProvider implements HasComponentRoutesInterface
{
    public function getComponentRoutes(): string
    {
        return __DIR__ . '/../Page';
    }
}
```

Les classes trouvées sont analysées comme les pages applicatives classiques :

- seuls les fichiers `*Page.php` sont pris en compte ;
- la classe doit étendre `AbstractPage` ;
- l'attribut `#[PageProperty]` est obligatoire pour exposer une route.

## Auto-enregistrement des namespaces de composants

Si votre provider implémente `HasComponentNamespacesInterface`, les namespaces retournés sont ajoutés à `component_namespaces`.

```php
use Impulse\Core\Contracts\HasComponentNamespacesInterface;

final class UiProvider extends AbstractProvider implements HasComponentNamespacesInterface
{
    public function getComponentNamespaces(): array
    {
        return [
            'Vendor\\Ui\\Component\\',
        ];
    }
}
```

### Effet concret

- les composants du namespace deviennent trouvables dans les templates ;
- la configuration est mise à jour et sauvegardée.

## Charger une configuration provider

Vous pouvez compléter la configuration applicative via `Config::loadProviderConfig()` si votre package expose son propre fichier de configuration.

```php
use Impulse\Core\Support\Config;

Config::loadProviderConfig(__DIR__ . '/../config/blog.php', 'blog');
```

## Ordre d'exécution

Pour chaque provider :

1. `register()` est appelé lors de la construction du kernel ;
2. `boot()` est appelé juste après, une fois tous les providers enregistrés.

## Bonnes pratiques

- utilisez `registerServices()` pour les bindings ;
- utilisez `onBoot()` pour les effets de boot ;
- gardez les providers idempotents autant que possible ;
- si votre module expose des pages ou composants, implémentez les interfaces dédiées plutôt que d'écrire une logique manuelle.
