# Layouts

Les layouts enveloppent les pages et centralisent la structure HTML commune, les slots, les assets partagés et les métadonnées de titre.

## Classe de base

Un layout étend `Impulse\Core\Component\AbstractLayout`.

```php
namespace App\Layout;

use Impulse\Core\Attributes\LayoutProperty;
use Impulse\Core\Component\AbstractLayout;
use Impulse\Core\Support\Collector\StyleCollector;

#[LayoutProperty(titlePrefix: 'Mon site')]
final class DefaultLayout extends AbstractLayout
{
    public function setup(): void
    {
        StyleCollector::addSheet('/css/app.css');
    }

    public function template(): string
    {
        return <<<HTML
            <header>Header</header>
            <main>{$this->slot()}</main>
            <footer>Footer</footer>
        HTML;
    }
}
```

## Choisir un layout

Ordre de priorité :

1. `layout()` sur la page ;
2. `layout:` dans `#[PageProperty]` ;
3. `template_layout` dans `impulse.php`.

## Slots de layout

Les pages peuvent alimenter des slots dédiés avec `<slot-layout>`.

### Dans la page

```html
<slot-layout name="hero">
    <section class="hero">Bienvenue</section>
</slot-layout>

<article>
    Contenu principal
</article>
```

### Dans le layout

```php
return <<<HTML
    <div class="hero-zone">{$this->slot('hero')}</div>
    <main>{$this->slot()}</main>
HTML;
```

### Comportement

- `slot()` retourne le contenu principal ;
- `slot('hero')` retourne le slot nommé `hero` ;
- les balises `<slot-layout>` sont retirées du corps principal avant injection.

## Titre HTML

Deux mécanismes existent :

- attribut `#[LayoutProperty(titlePrefix: ..., titleSuffix: ...)]`
- méthodes `titlePrefix()` et `titleSuffix()`

Le routeur compose le titre final à partir du titre de page et de ces métadonnées.

## Styles et scripts

Un layout est aussi un composant. Il peut donc :

- surcharger `style()` ;
- surcharger `script()` ;
- appeler `StyleCollector::addSheet()` ;
- appeler `ScriptCollector::addFile()`.

Contrairement aux composants classiques, le style d'un layout n'est pas scoppé automatiquement.

## Particularités des layouts

- le wrapper rendu par un layout reçoit `id="app"` ;
- le layout est rendu après la page ;
- il a accès à la route courante comme n'importe quel composant.

## Bonnes pratiques

- utilisez un layout pour la structure commune de page, pas pour la logique métier ;
- préférez `LayoutProperty` pour les métadonnées simples ;
- réservez `titlePrefix()` et `titleSuffix()` aux cas dynamiques ;
- centralisez dans le layout les assets globaux du front.
