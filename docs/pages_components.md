# Pages, composants et routage

Ce document décrit succinctement la manière de déclarer des pages et des composants ainsi que le fonctionnement du routage.

## PageProperty

Les pages sont annotées avec `#[PageProperty(...)]` pour déclarer la route, le titre, le layout, etc. Exemple :

```php
#[PageProperty(
    route: '/',
    name: 'IndexPage',
    title: 'Se connecter',
    layout: App\Layout\Default\DefaultLayout::class
)]
final class IndexPage extends AbstractPage
{
    public function template(): string
    {
        return $this->view('pages.index');
    }
}
```

## Router component

Le composant `Impulse\Core\Component\BuiltIn\Router` permet de générer des liens compatibles avec la navigation client-side d'Impulse (il rend `<a href="..." data-router>`). Utilisez ce composant ou ajoutez `data-router` sur vos liens pour activer la navigation Impulse.

## Layouts et slots

Les pages peuvent fournir des slots pour être consommés par les layouts via la balise `<slot-layout name="...">`.


