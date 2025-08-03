<?php

declare(strict_types=1);

namespace Impulse\Core\Component\Handler;

use Impulse\Core\App;
use Impulse\Core\Attributes\Action;
use Impulse\Core\Component\Resolver\ComponentResolver;
use Impulse\Core\Component\Store\Store;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Contracts\EventInterface;
use Impulse\Core\Event\EventDispatcher;
use Impulse\Core\Exceptions\AjaxDispatcherException;
use Impulse\Core\Http\Request;
use Impulse\Core\Support\Collection\StateCollection;
use Impulse\Core\Support\Helper;
use Impulse\Core\Support\Collector\StyleCollector;
use Impulse\Core\Support\DevError;
use JetBrains\PhpStorm\NoReturn;
use ScssPhp\ScssPhp\Exception\SassException;

final class AjaxDispatcher
{
    private const MAX_PAYLOAD_SIZE = 1048576; // 1MB

    /**
     * @throws \JsonException
     */
    private function parseRequest(): array
    {
        Request::normalizeJsonPost();

        $raw = file_get_contents('php://input');
        if (strlen($raw) > self::MAX_PAYLOAD_SIZE) {
            DevError::respond('Charge utile trop volumineuse');
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            DevError::respond('Erreur de décodage JSON : ' . $e->getMessage());
        }

        if (!is_array($data)) {
            DevError::respond('Requête invalide');
        }

        return $data;
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    private function processEmit(array $data): void
    {
        if (!isset($data['emit'])) {
            return;
        }

        $event = $data['emit'];
        $payload = $data['payload'] ?? [];
        $componentIds = $data['components'] ?? [];

        $results = [];
        foreach ($componentIds as $componentId) {
            $result = $this->processComponentEvent($componentId, $event, $payload);
            if ($result) {
                $results[] = $result;
            }
        }

        $response = ['updates' => $results];

        if ($localStorage = Store::getAllDataLocalStorage()) {
            $response['localStorage'] = $localStorage;
        }

        if ($styles = StyleCollector::renderStyle()) {
            $response['styles'] = $styles;
        }

        header('Content-Type: application/json');
        echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    private function resolveComponent(array $data): ComponentInterface
    {
        if (!isset($data['id'])) {
            DevError::respond("L'ID du composant est requis");
        }

        $defaults = [];
        if (isset($data['slot'])) {
            $defaults['__slot'] = $data['slot'];
        }

        /** @var ComponentInterface $component */
        $component = ComponentResolver::resolve($data['id'], $defaults);
        if (!$component) {
            DevError::respond("Composant non trouvé pour l'ID : {$data['id']}", 404);
        }

        if (isset($data['states']) && is_array($data['states'])) {
            foreach ($data['states'] as $name => $value) {
                if (is_string($value) && StateCollection::shouldConvertValue($component, $name, $value)) {
                    if (Helper::isValidateJson($value)) {
                        $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                        $value = is_array($decoded) ? $decoded : [$decoded];
                    } else {
                        $value = [$value];
                    }
                }

                $component->state($name, $value)->set($value);
            }
        }

        return $component;
    }

    /**
     * @throws \JsonException
     */
    private function executeAction(ComponentInterface $component, array $data): ?bool
    {
        if (!isset($data['action'])) {
            return null;
        }

        try {
            $rawAction = $data['action'];
            $args = [];

            if (preg_match('/^([a-zA-Z_]\w*)\((.*)\)$/', $rawAction, $matches)) {
                $method = $matches[1];
                $argsString = $matches[2];

                if (trim($argsString) !== '') {
                    $args = array_map('trim', explode(',', $argsString));
                    $args = array_map(static function ($value) {
                        if (is_numeric($value)) {
                            return $value + 0;
                        }

                        return trim($value, '"\'');
                    }, $args);
                }
            } else {
                $method = $rawAction;
            }

            $component->onBeforeAction($method, $args);
            $actionCalled = false;
            $actionResult = null;

            if (method_exists($component, $method)) {
                $refMethod = new \ReflectionMethod($component, $method);
                if (
                    $refMethod->getAttributes(Action::class)
                    && $refMethod->isPublic()
                    && !str_starts_with($method, '__')
                ) {
                    if (array_key_exists('value', $data) && count($args) < $refMethod->getNumberOfParameters()) {
                        $args[] = $data['value'];
                    }

                    $actionResult = call_user_func_array([$component, $method], $args);
                    $actionCalled = true;
                } elseif ($refMethod->getAttributes(Action::class) && !$refMethod->isPublic()) {
                    DevError::respond("La méthode '$method' est décorée avec #[Action] mais n'est pas publique : elle ne pourra pas être appelée.");
                }
            }

            $methods = $component->getMethods();
            if (!$actionCalled && $methods->exists($method)) {
                $callable = $methods->get($method);
                if (empty($args) && array_key_exists('value', $data)) {
                    $args = [$data['value']];
                }

                $ref = new \ReflectionFunction($callable);
                $requiredParams = $ref->getNumberOfRequiredParameters();
                if (count($args) < $requiredParams) {
                    throw new AjaxDispatcherException("La méthode '$method' attend au moins $requiredParams argument(s), " . count($args) . " fourni(s).");
                }

                if (!is_callable($callable)) {
                    throw new AjaxDispatcherException("La méthode '$method' est introuvable ou non appelable dans le composant.");
                }

                $actionResult = $callable(...$args);
                $actionCalled = true;
            }

            if (!$actionCalled) {
                throw new AjaxDispatcherException("'$method' non trouvée dans les méthodes définies.");
            }

            return $actionResult;
        } catch (AjaxDispatcherException|\ReflectionException $e) {
            DevError::respond('Erreur lors de l\'exécution de l\'action : ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @throws \JsonException
     * @throws \Exception
     */
    private function renderResponse(ComponentInterface $component, array $data): string
    {
        $component->onAfterAction();
        $html = $component->render($data['update'] ?? null);

        if (!empty($data['requestStates'])) {
            $dataStates = $component->shouldExposeStates()
                ? htmlspecialchars(json_encode(
                    $component->exposedStates(),
                    JSON_THROW_ON_ERROR
                ), ENT_QUOTES, 'UTF-8')
                : null;

            $response = [
                'html' => $html,
            ];

            if ($dataStates) {
                $response['states'] = $dataStates;
            }

            header('Content-Type: application/json');
            return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (!empty($data['update'])) {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<!DOCTYPE html><meta charset="utf-8">' . $html);
            libxml_clear_errors();
            $xpath = new \DOMXPath($dom);
            $node = $xpath->query("//*[@data-update='{$data['update']}']")->item(0);

            if ($node) {
                $fragmentHtml = '';
                if ($node->hasChildNodes()) {
                    foreach ($node->childNodes as $child) {
                        $fragmentHtml .= $dom->saveHTML($child);
                    }
                } else {
                    $fragmentHtml = $dom->saveHTML($node);
                }

                $dataStates = $component->shouldExposeStates()
                    ? htmlspecialchars(json_encode(
                        $component->exposedStates(),
                        JSON_THROW_ON_ERROR
                    ), ENT_QUOTES, 'UTF-8')
                    : null;

                $response = [
                    'result' => $fragmentHtml ?: $dom->saveHTML($node),
                ];

                if ($dataStates) {
                    $response['states'] = $dataStates;
                }

                header('Content-Type: application/json');
                return json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $html;
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     */
    public function dispatchQueuedEvents(array $queuedEvents): array
    {
        $results = [];
        $componentIds = explode(',', $_SERVER['HTTP_X_IMPULSE_COMPONENTS'] ?? '');

        foreach ($queuedEvents as $event) {
            if (!$event instanceof EventInterface) {
                continue;
            }

            foreach ($componentIds as $componentId) {
                $result = $this->processComponentEvent($componentId, $event->name(), $event->payload());
                if ($result) {
                    $results[] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     * @throws \DOMException
     * @throws SassException
     * @throws \Exception
     */
    public function handle(): void
    {
        $data = $this->parseRequest();

        if (!empty($data['emit'])) {
            $this->processEmit($data);
            return;
        }

        header('Content-Type: application/json');

        $component = $this->resolveComponent($data);
        $actionResult = $this->executeAction($component, $data);
        $html = $this->renderResponse($component, $data);
        $emittedUpdates = $this->dispatchQueuedEvents(EventDispatcher::getInstance()->flush());

        $response = $this->buildResponse($component, $html, $actionResult, $emittedUpdates);
        $this->sendJsonResponse($response);
    }

    /**
     * @throws \JsonException|SassException
     */
    private function buildResponse(ComponentInterface $component, string $html, mixed $actionResult, array $emittedUpdates): array
    {
        $baseResponse = Helper::isValidateJson($html) ? json_decode($html, true, 512, JSON_THROW_ON_ERROR) : [];
        $updates = $this->buildUpdates($component, $html, $actionResult, $emittedUpdates);

        $response = array_merge($baseResponse, ['updates' => $updates]);

        $this->addOptionalResponseData($response, $component);

        return $response;
    }

    private function buildUpdates(ComponentInterface $component, string $html, mixed $actionResult, array $emittedUpdates): array
    {
        $updates = $emittedUpdates;

        if ($actionResult !== false && !$this->componentAlreadyInUpdates($component, $emittedUpdates)) {
            $updates[] = [
                'component' => $component->getComponentId(),
                'html' => trim($html),
                'result' => true,
            ];
        }

        return $updates;
    }

    private function componentAlreadyInUpdates(ComponentInterface $component, array $updates): bool
    {
        return !empty(array_filter($updates, static fn($u) => $u['component'] === $component->getComponentId()));
    }

    /**
     * @throws \JsonException
     * @throws SassException
     */
    private function addOptionalResponseData(array &$response, ComponentInterface $component): void
    {
        if ($component->shouldExposeStates()) {
            $response['states'] = htmlspecialchars(
                json_encode($component->exposedStates(), JSON_THROW_ON_ERROR),
                ENT_QUOTES,
                'UTF-8'
            );
        }

        if ($styles = StyleCollector::renderStyle()) {
            $response['styles'] = $styles;
        }

        if ($localStorage = Store::getAllDataLocalStorage()) {
            $response['localStorage'] = $localStorage;
        }
    }

    /**
     * @throws \JsonException
     */
    #[NoReturn]
    private function sendJsonResponse(array $response): void
    {
        echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @throws \JsonException
     * @throws \ReflectionException
     */
    private function processComponentEvent(string $componentId, string $eventName, mixed $payload): ?array
    {
        $component = ComponentResolver::resolve($componentId);
        if (!$component) {
            return null;
        }

        if (method_exists($component, 'boot')) {
            App::container()->call([$component, 'boot']);
        }

        if (method_exists($component, 'onEvent')) {
            $result = $component->onEvent($eventName, $payload);
            if ($result !== false) {
                return [
                    'component' => $component->getComponentId(),
                    'result' => $result,
                    'html' => $component->render(),
                ];
            }
        }

        return null;
    }
}
