# Attributs (Attributes)

Le package `impulsephp/core` fournit plusieurs attributs PHP pour déclarer des métadonnées sur les classes `Page` et `Layout`.

## `#[PageProperty(...)]`
Voir la documentation existante dans le README (déclaration de route, titre, layout, etc.).

## `#[LayoutProperty(prefix: ?string, suffix: ?string)]`

L'attribut `LayoutProperty` permet de déclarer directement des métadonnées de layout au-dessus de la classe. Il est principalement utilisé pour fournir un préfixe ou un suffixe appliqué au titre des pages rendues avec ce layout.

Exemple :

```php
use Impulse\Core\Attributes\LayoutProperty;

#[LayoutProperty(prefix: 'ImpulsePHP', suffix: 'MonSite')]
final class DefaultLayout extends AbstractLayout
{
    // ...
}
```

### Priorité d'application
- L'attribut `LayoutProperty` est consulté avant d'instancier le layout pour lire `titlePrefix` / `titleSuffix`.
- Si l'attribut n'existe pas, le router instancie le layout et appelle `titlePrefix()` / `titleSuffix()`.

### Remarques
- L'attribut est facultatif. Il offre une manière déclarative et simple d'ajouter un préfixe/suffixe global pour les pages utilisant le layout.
- L'attribut est compatible avec la compatibilité descendante : si vos layouts utilisent déjà `titlePrefix()` cela continuera de fonctionner.

