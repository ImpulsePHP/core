<?php

declare(strict_types=1);

namespace Impulse\Core\Component;

use Impulse\Core\App;
use Impulse\Core\Attributes\Action;
use Impulse\Core\Component\BuiltIn\Router;
use Impulse\Core\Component\Resolver\ComponentResolver;
use Impulse\Core\Component\State\State;
use Impulse\Core\Component\Transformer\ComponentHtmlTransformer;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Event\Event;
use Impulse\Core\Event\EventDispatcher;
use Impulse\Core\Exceptions\ImpulseException;
use Impulse\Core\Http\Request;
use Impulse\Core\Kernel\Impulse;
use Impulse\Core\Http\Router\PageRouter;
use Impulse\Core\Cache\PageCacheManager;
use Impulse\Core\Support\Collection\MethodCollection;
use Impulse\Core\Support\Collection\StateCollection;
use Impulse\Core\Support\Collection\WatcherCollection;
use Impulse\Core\Support\Collector\ScriptCollector;
use Impulse\Core\Support\Collector\StyleCollector;
use Impulse\Core\Support\Profiler;
use ScssPhp\ScssPhp\Exception\SassException;

abstract class AbstractComponent implements ComponentInterface
{
    private string $componentId;
    private string $slot = '';
    private ?string $currentRoute;

    public ?string $tagName = null;
    protected bool $cacheEnabled = true;

    /**
     * @var array<string, mixed>
     */
    private array $namedSlots = [];

    /**
     * @var array<string, mixed>
     */
    private array $defaults;

    private WatcherCollection $watchers;
    private MethodCollection $methods;
    private StateCollection $stateCache;

    abstract public function template(): string;

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function __construct(string $id, ?string $route = null, array $defaults = [])
    {
        $this->componentId = $id;
        $this->defaults = $defaults;
        $this->currentRoute = $route;

        if (isset($defaults['__slot'])) {
            $this->slot = $defaults['__slot'];
        }

        foreach ($defaults as $key => $value) {
            if (str_starts_with($key, '__slot:')) {
                $slotName = substr($key, 8);
                $this->namedSlots[$slotName] = $value;
            }
        }

        $this->methods = new MethodCollection();
        $this->stateCache = new StateCollection();
        $this->watchers = new WatcherCollection();

        if (!$this->cacheEnabled) {
            PageCacheManager::disable();
        }

        $this->stateCache->setComponent($this);

        if (method_exists($this, 'boot')) {
            App::container()->call([
                $this,
                'boot',
            ]);
        }

        $this->setup();

        foreach ($this->defaults as $key => $value) {
            if (is_scalar($value) && !str_starts_with($key, '__slot')) {
                $prefixed = $this->prefixState($key);
                if (!$this->stateCache->has($prefixed)) {
                    $this->state($key, $value);
                }
            }
        }
    }

    protected function prefixState(string $name): string
    {
        return $this->getComponentId() . '__' . $name;
    }

    private function unprefixState(string $prefixed): string
    {
        $prefix = $this->getComponentId() . '__';
        if (str_starts_with($prefixed, $prefix)) {
            return substr($prefixed, strlen($prefix));
        }

        return $prefixed;
    }

    public function rawStates(): array
    {
        return $this->stateCache->all();
    }

    public function __get(string $name): mixed
    {
        return $this->stateCache->getValue($this->prefixState($name));
    }

    public function __set(string $name, mixed $value): void
    {
        if (property_exists($this, $name)) {
            trigger_error("Ne pas déclarer de propriété publique '$name' dans les composants : utilisez \$this->state('$name', ...)", E_USER_WARNING);
        }

        $this->stateCache->setValue($this->prefixState($name), $value);
    }

    public function __isset(string $name): bool
    {
        return $this->stateCache->has($this->prefixState($name));
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->methods->call($name, $arguments);
    }

    public function setup(): void { }
    public function onBeforeAction(?string $method = null, array $args = []): void {}
    public function onAfterAction(): void {}

    public function getComponentId(): string
    {
        return $this->componentId;
    }

    public function getCurrentRoute(): ?string
    {
        return $this->currentRoute;
    }

    public function getNameCurrentRoute(): ?string
    {
        if (!$this->currentRoute) {
            return null;
        }

        $router = PageRouter::instance();
        return $router?->findComponentForRoute($this->currentRoute)?->name;
    }

    public function getRequest(): Request
    {
        static $request = null;
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        return $request;
    }

    /**
     * @throws \ReflectionException
     */
    public function getMethods(): MethodCollection
    {
        $methods = clone $this->methods;

        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Action::class);
            if (empty($attributes)) {
                continue;
            }

            $methods->register($method->getName(), $method->getClosure($this));
        }

        return $methods;
    }

    public function state(string $name, mixed $defaultValue, ?array $allowedValues = null, bool $protected = false): State
    {
        $prefixedName = $this->prefixState($name);
        $defaultValue = array_key_exists($name, $this->defaults) ? $this->defaults[$name] : $defaultValue;

        return $this->stateCache->getOrCreate($prefixedName, $defaultValue, $allowedValues, $protected);
    }

    /**
     * @param array $states Format: ['nom' => valeur] ou ['nom' => [valeur, allowedValues]] ou ['nom' => [valeur, allowedValues, protected]]
     */
    public function states(array $states): void
    {
        foreach ($states as $name => $config) {
            if (is_array($config)) {
                // Format: ['nom' => [defaultValue, allowedValues?, protected?]]
                $defaultValue = $config[0] ?? null;
                $allowedValues = $config[1] ?? null;
                $protected = $config[2] ?? false;

                $this->state($name, $defaultValue, $allowedValues, $protected);
            } else {
                // Format simple: ['nom' => defaultValue]
                $this->state($name, $config);
            }
        }
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getStates(): array
    {
        $states = [];
        foreach ($this->stateCache as $name => $state) {
            $originalName = $this->unprefixState($name);
            $states[$originalName] = $this->stateCache->getValue($name);
        }

        return $states;
    }

    public function exposedStates(): array
    {
        // Exemple d'utilisation: return Arr::only($this->getStates(), ['label', 'required']);

        $states = [];
        foreach ($this->stateCache as $name => $state) {
            $originalName = $this->unprefixState($name);
            $states[$originalName] = $this->stateCache->getValue($name);
        }

        return $states;
    }

    public function style(): ?string
    {
        return null;
    }

    public function isScopedStyle(): bool
    {
        return true;
    }

    public function script(): ?string
    {
        return null;
    }

    public function getTemplateName(): string
    {
        return get_class($this);
    }

    public function getViewData(): array
    {
        return get_object_vars($this);
    }

    public function setSlot(string $name, mixed $content): void
    {
        if (is_bool($content)) {
            $content = '';
        } elseif (is_null($content) || $content === 'false') {
            $content = '';
        } elseif (!is_string($content)) {
            $content = (string) $content;
        }

        $this->namedSlots[$name] = $content;
    }

    public function slot(string $name = '__slot'): string
    {
        if ($name === '__slot') {
            return $this->slot;
        }

        return $this->namedSlots[$name] ?? '';
    }

    public function getNamedSlots(): array
    {
        return $this->namedSlots;
    }

    public function watch(string|array $statesName, callable $callback): void
    {
        foreach ((array) $statesName as $stateName) {
            if (!$this->stateCache->hasValue($stateName)) {
                throw new \InvalidArgumentException("State key '{$stateName}' does not exist.");
            }

            $this->watchers->set($stateName, $callback);
        }
    }

    public function getWatchers(): WatcherCollection
    {
        return $this->watchers;
    }

    public function disablePageCache(): void
    {
        PageCacheManager::disable();
    }

    public function layout(): ?string
    {
        return null;
    }

    public function emit(string $event, mixed $payload = null): void
    {
        EventDispatcher::getInstance()->queue(new Event($event, $payload));
    }

    public function shouldExposeStates(): bool
    {
        return false;
    }

    /**
     * @throws \JsonException
     */
    public function render(?string $update = null): string
    {
        ComponentResolver::registerNamespaceFromInstance($this);

        Profiler::start('render:' . static::class);

        $dataStates = $this->prepareDataStates();
        $slotAttr = $this->getSlotAttribute();
        $template = $this->transformTemplate($this->template());

        $this->collectAssets();

        if ($update !== null && !str_contains($update, '@')) {
            $fragments = $this->extractUpdateFragments($template, $update, $dataStates);
            if ($fragments !== null) {
                Profiler::stop('render:' . static::class);
                return $fragments;
            }
        }

        $content = $this->renderContent($template);

        if ($this instanceof AbstractPage || $this->getTemplateName() === Router::class) {
            Profiler::stop('render:' . static::class);
            return $content;
        }

        $html = $this->wrapContent($content, $slotAttr, $dataStates);

        Profiler::stop('render:' . static::class);

        return $html;
    }

    /**
     * @throws \JsonException
     */
    private function prepareDataStates(): ?string
    {
        if (!$this->shouldExposeStates()) {
            return null;
        }

        return htmlspecialchars(
            json_encode($this->exposedStates(), JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    private function transformTemplate(string $rawTemplate): string
    {
        Profiler::start('template:' . static::class);
        $template = ComponentHtmlTransformer::getInstance()->process($rawTemplate);
        Profiler::stop('template:' . static::class);

        return $template;
    }

    /**
     * @throws \JsonException
     */
    private function collectAssets(): void
    {
        if ($style = $this->style()) {
            $css = $style;
            if ($this->isScopedStyle()) {
                $scopedAttr = "[data-impulse-id=\"{$this->componentId}\"]";
                $css = preg_replace('/\s*([^{}]+?)\s*\{/', $scopedAttr . ' $1 {', $style);
            }

            StyleCollector::addCss($css);
        }

        if ($script = $this->script()) {
            ScriptCollector::addCode($script);
        }
    }

    /**
     * @throws \JsonException
     */
    private function extractUpdateFragments(string $template, string $group, ?string $dataStates): ?string
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<!DOCTYPE html><meta charset="utf-8">' . $template);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $fragments = [];
        foreach ($xpath->query("//*[@data-update]") as $node) {
            $attr = $node->getAttribute('data-update');
            if (str_starts_with($attr, $group . '@')) {
                $parts = explode('@', $attr, 2);
                if (isset($parts[1])) {
                    $key = $parts[1];
                    $fragments["{$group}@{$key}"] = $dom->saveHTML($node);
                }
            }
        }

        if (empty($fragments)) {
            return null;
        }

        $result = [
            'fragments' => $fragments,
            'styles' => StyleCollector::renderStyle(),
        ];

        if ($dataStates) {
            $result['states'] = $dataStates;
        }

        return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    private function renderContent(string $template): string
    {
        if (trim($template) !== '') {
            return trim($template);
        }

        $renderer = Impulse::renderer();
        if (!$renderer) {
            return '';
        }

        try {
            return $renderer->render($this->getTemplateName(), $this->getViewData());
        } catch (\InvalidArgumentException $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                return '';
            }

            throw $e;
        }
    }

    private function wrapContent(string $content, string $slotAttr, ?string $dataStates): string
    {
        $divApp = $this instanceof AbstractLayout ? ' id="app"' : null;
        $dataStates = $dataStates ? ' data-states="' . $dataStates . '"' : null;

        $id = $this->getComponentId();

        return <<<HTML
            <div$divApp data-impulse-id="$id" $dataStates$slotAttr>
                {$content}
            </div>
        HTML;
    }

    public function view(string $template, array $data = []): string
    {
        $renderer = Impulse::renderer();
        if (!$renderer) {
            throw new ImpulseException("Aucun moteur de rendu n'est défini. Impossible d'appeler view().");
        }

        return $renderer->render($template, $data ?: $this->getViewData());
    }

    private function getSlotAttribute(): string
    {
        $slotEncoded = base64_encode($this->slot);

        if ($this instanceof AbstractPage) {
            return '';
        }

        if ($this instanceof AbstractLayout) {
            return '';
        }

        if ($slotEncoded !== '') {
            return ' data-slot="' . htmlspecialchars($slotEncoded, ENT_QUOTES) . '"';
        }

        return '';
    }

    public function getStateMetadata(string $name): ?array
    {
        $prefixedName = $this->prefixState($name);

        if (!$this->stateCache->has($prefixedName)) {
            return null;
        }

        $state = $this->stateCache->get($prefixedName);
        $allowedValues = $state->getAllowedValues();

        return [
            'type' => $this->inferFieldType($state->get(), $allowedValues),
            'allowedValues' => $allowedValues,
            'label' => $this->generateLabel($name),
            'defaultValue' => $state->get()
        ];
    }

    public function getAllStatesMetadata(): array
    {
        $metadata = [];
        foreach ($this->stateCache as $prefixedName => $state) {
            $name = $this->unprefixState($prefixedName);
            $metadata[$name] = $this->getStateMetadata($name);
        }

        return $metadata;
    }

    private function inferFieldType(mixed $value, ?array $allowedValues): string
    {
        if ($allowedValues !== null) {
            return 'select';
        }

        return match (gettype($value)) {
            'boolean' => 'boolean',
            'integer', 'double' => 'number',
            'array' => 'array',
            default => 'string'
        };
    }

    private function generateLabel(string $name): string
    {
        return ucfirst(str_replace('_', ' ', $name));
    }

    protected function getStateCache(): StateCollection
    {
        return $this->stateCache;
    }
}
