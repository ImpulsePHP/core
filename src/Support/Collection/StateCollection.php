<?php

declare(strict_types=1);

namespace Impulse\Core\Support\Collection;

use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Component\State\State;
use Impulse\Core\Contracts\ComponentInterface;
use Impulse\Core\Support\Helper;

final class StateCollection implements \IteratorAggregate, \Countable
{
    use CollectionTrait;

    private static array $phpDocCache = [];
    private ?AbstractComponent $component = null;

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function getOrCreate(string $name, mixed $defaultValue, ?array $allowedValues = null, bool $protected = false): State
    {
        if (!$this->has($name)) {
            $state = new State($defaultValue, $allowedValues, $protected);
            if ($this->component) {
                $state->attach($this->component, $name);
            }

            $this->set($name, $state);
        }

        /** @var State $state */
        $state = $this->get($name);
        if ($this->component) {
            $state->attach($this->component, $name);
        }

        return $state;
    }

    public static function analyzePhpDocType(ComponentInterface $component, string $name): array
    {
        $className = get_class($component);

        $prefix = $component->getComponentId() . '__';
        $propertyName = str_starts_with($name, $prefix) ? substr($name, strlen($prefix)) : $name;

        $cacheKey = $className . '::' . $propertyName;

        if (isset(self::$phpDocCache[$cacheKey])) {
            return self::$phpDocCache[$cacheKey];
        }

        if (!isset(self::$phpDocCache[$className . '__parsed'])) {
            self::preParseClassDoc($className);
        }

        return self::$phpDocCache[$cacheKey] ?? ['types' => ['mixed'], 'isUnion' => false, 'acceptsArray' => false];
    }

    private static function preParseClassDoc(string $className): void
    {
        $ref = new \ReflectionClass($className);
        $doc = $ref->getDocComment() ?: '';

        self::$phpDocCache[$className . '__parsed'] = true;

        if (preg_match_all('/@property\s+(\S+)\s+\$(\w+)/', $doc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $typeString = strtolower(trim($match[1]));
                $propertyName = $match[2];
                $cacheKey = $className . '::' . $propertyName;

                self::$phpDocCache[$cacheKey] = self::parseTypeString($typeString);
            }
        }
    }

    private static function parseTypeString(string $typeString): array
    {
        if (str_contains($typeString, '|')) {
            $types = array_map('trim', explode('|', $typeString));
            $acceptsArray = in_array('array', $types, true) ||
                           array_filter($types, static fn($t) => str_contains($t, '[]'));

            return [
                'types' => $types,
                'isUnion' => true,
                'acceptsArray' => $acceptsArray
            ];
        }

        $acceptsArray = $typeString === 'array' || str_contains($typeString, '[]');

        return [
            'types' => [$typeString],
            'isUnion' => false,
            'acceptsArray' => $acceptsArray
        ];
    }

    public static function shouldConvertValue(ComponentInterface $component, string $name, mixed $value): bool
    {
        $typeInfo = self::analyzePhpDocType($component, $name);
        if (in_array('mixed', $typeInfo['types'], true)) {
            return false;
        }

        if (!$typeInfo['acceptsArray']) {
            return false;
        }

        return is_string($value);
    }

    public function getValue(string $name): mixed
    {
        $state = $this->get($name);
        if (!$state) {
            return null;
        }

        $value = $state->get();

        if (!$this->component) {
            return $value;
        }

        $typeInfo = self::analyzePhpDocType($this->component, $name);

        if (in_array('array', $typeInfo['types'], true)) {
            if ($value === false || $value === null || $value === '' || $value === '[]') {
                return [];
            }

            if (is_string($value) && self::shouldConvertValue($this->component, $name, $value)) {
                return $this->convertStringToArray($value);
            }

            if (!is_array($value)) {
                return [$value];
            }
        }

        return $value;
    }

    private function convertStringToArray(string $value): array
    {
        if (Helper::isValidateJson($value)) {
            try {
                $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : [$decoded];
            } catch (\JsonException) {
                // JSON invalide, continuer
            }
        }

        return [$value];
    }

    public function hasValue(string $name): bool
    {
        return $this->has($name) && $this->get($name)?->get() !== null;
    }

    public function setValue(string $name, mixed $value): void
    {
        $state = $this->get($name);
        if ($state) {
            $oldValue = $state->get();
            $state->set($value);

            if ($this->component) {
                $watchers = $this->component->getWatchers();
                if ($watchers->has($name)) {
                    foreach ($watchers->get($name) as $callback) {
                        $callback($value, $oldValue);
                    }
                }
            }
        }
    }

    public function setComponent(AbstractComponent $component): void
    {
        $this->component = $component;
        foreach ($this->items as $name => $state) {
            if ($state instanceof State) {
                $state->attach($component, (string) $name);
            }
        }
    }
}
