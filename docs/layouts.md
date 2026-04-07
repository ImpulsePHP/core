# Layouts

Les layouts permettent d'envelopper le rendu d'une page et d'y définir des portions réutilisables (slots), des styles et des métadonnées partagées.

Impulse fournit une classe de base `Impulse\Core\Component\AbstractLayout` que vous pouvez étendre pour créer vos propres layouts.

## Méthodes disponibles

- `titlePrefix(): ?string` — (optionnel) retourne une chaîne qui sera préfixée devant le titre de la page.
- `titleSuffix(): ?string` — (optionnel) retourne une chaîne qui sera suffixée après le titre de la page.
- `isScopedStyle(): bool` — (hérité) permet de déterminer si les styles du layout doivent être scoper.

Ces méthodes sont définies dans `AbstractLayout` avec des valeurs par défaut (null / false). Vous pouvez les surcharger dans votre layout pour fournir un comportement personnalisé.

## Utilisation par méthode (exemple)

```php
namespace App\Layout\Default;

use Impulse\Core\Component\AbstractLayout;
use Impulse\Core\Support\Collector\StyleCollector;

final class DefaultLayout extends AbstractLayout
{
    public function setup(): void
    {
        StyleCollector::addSheet('/css/main.css');
    }

    public function titlePrefix(): ?string
    {
        return 'ImpulsePHP';
    }

    public function template(): string
    {
        return $this->view('layouts.default', [
            'slot' => $this->slot(),
        ]);
    }
}
```

Toutes les pages utilisant ce layout verront leur titre préfixé automatiquement par `DefaultLayout::titlePrefix()`.

## Utilisation via attribut (préférence déclarative)

Vous pouvez également déclarer un préfixe et/ou suffixe directement sur la classe de layout en utilisant l'attribut `#[LayoutProperty(prefix: ..., suffix: ...)]`.

Avantages :
- Syntaxe déclarative plus compacte.
- Lisibilité : les métadonnées sont visibles au-dessus de la classe.

Exemple :

```php
use Impulse\Core\Attributes\LayoutProperty;

#[LayoutProperty(prefix: 'ImpulsePHP')]
final class DefaultLayout extends AbstractLayout
{
    // ...
}
```

L'implémentation du router applique la logique suivante (priorité) :

1. Si la classe de layout possède un attribut `LayoutProperty`, ses valeurs `prefix`/`suffix` sont utilisées.
2. Sinon, si l'instance du layout implémente `titlePrefix()` / `titleSuffix()`, ces méthodes sont appelées.
3. Le titre final de la page devient : `prefix - pageTitle - suffix`, en ignorant les parties manquantes.

## Slots et rendu

Les layouts supportent les slots via la balise `<slot-layout name="...">` dans les templates de page. Le `LayoutManager` extrait ces slots et les fournit en tant que props au layout lors de son instanciation.

## Bonnes pratiques

- Préférez la déclaration via attribut pour un comportement simple et lisible.
- Si votre layout a besoin de logique (ex. récupération de la valeur dans la config), surchargez `titlePrefix()` pour retourner dynamiquement la valeur.
- Gardez le séparateur ` - ` comme convention par défaut ; changez la composition si vous avez des besoins particuliers.


