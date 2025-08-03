<?php

declare(strict_types=1);

namespace Impulse\Core\Component\State;

use Impulse\Core\Component\AbstractComponent;
use Impulse\Core\Contracts\StateInterface;
use Impulse\Core\Support\Config;
use Random\RandomException;

class State implements StateInterface
{
    private ?AbstractComponent $component = null;
    private bool $protected;
    private string $name = '';
    private mixed $value;
    private ?array $allowedValues;
    private bool $isAttached = false;

    public function __construct(mixed $defaultValue = null, ?array $allowedValues = null, bool $protected = false)
    {
        $this->value = $defaultValue;
        $this->allowedValues = $allowedValues;
        $this->protected = $protected;
    }

    public function attach(AbstractComponent $component, string $name): void
    {
        if ($this->isAttached && $this->component === $component && $this->name === $name) {
            return;
        }

        $this->component = $component;
        $this->name = $name;
        $this->isAttached = true;
    }

    /**
     * @throws \JsonException
     */
    private static function getSecretKey(): string
    {
        $key = Config::get('state_encryption_key');
        if (!$key) {
            throw new \RuntimeException(
                'Clé de chiffrement manquante. Ajoutez "state_encryption_key" dans votre fichier impulse.php'
            );
        }

        if (strlen($key) < 32) {
            throw new \RuntimeException(
                'La clé de chiffrement doit faire au moins 32 caractères'
            );
        }

        return $key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    /**
     * @throws \JsonException
     */
    public function set(mixed $value): void
    {
        if ($this->allowedValues !== null && !in_array($value, $this->allowedValues, true)) {
            $list = implode(', ', array_map(static function ($v) {
                if (is_string($v)) {
                    return "'$v'";
                }

                if ($v === null) {
                    return 'null';
                }

                return (string) $v;
            }, $this->allowedValues));

            throw new \InvalidArgumentException("Invalid value for state '{$this->name}'. Allowed values: {$list}");
        }

        if (is_string($value) && str_starts_with($value, 'impulse:')) {
            $decryptedValue = self::decrypt($value);
            if ($decryptedValue !== null) {
                $value = $decryptedValue;
            }
        }

        if ($this->value !== $value) {
            $old = $this->value;
            $this->value = $value;

            if ($this->component) {
                $watchers = $this->component->getWatchers();
                if ($watchers->has($this->name)) {
                    foreach ($watchers->get($this->name) as $callback) {
                        $callback($value, $old);
                    }
                }
            }
        }
    }

    public function isProtected(): bool
    {
        return $this->protected;
    }

    public function getForDom(): mixed
    {
        return $this->getValue();
    }

    /**
     * @throws \JsonException
     */
    private static function safeSerialization(mixed $value): string
    {
        if (is_string($value) || is_array($value) || is_numeric($value) || is_bool($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        if (is_object($value)) {
            $value = (array) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    private static function safeDeserialization(string $data): mixed
    {
        try {
            return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return $data;
        }
    }

    /**
     * @throws RandomException
     * @throws \JsonException
     */
    private static function encrypt(mixed $value): string
    {
        $data = self::safeSerialization($value);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', self::getSecretKey(), 0, $iv);

        return 'impulse:' . base64_encode($iv . $encrypted . hash('sha256', $data));
    }

    public static function decrypt(string $encryptedData): mixed
    {
        try {
            if (!str_starts_with($encryptedData, 'impulse:')) {
                return null;
            }

            $encryptedData = substr($encryptedData, 8);

            $data = base64_decode($encryptedData);
            if ($data === false) {
                return null;
            }

            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16, -64);
            $checksum = substr($data, -64);

            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', self::getSecretKey(), 0, $iv);
            if ($decrypted === false) {
                return null;
            }

            if (hash('sha256', $decrypted) !== $checksum) {
                return null;
            }

            return self::safeDeserialization($decrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getAllowedValues(): ?array
    {
        return $this->allowedValues;
    }

    public function __toString(): string
    {
        $value = $this->get();

        if (is_array($value) || is_object($value)) {
            try {
                return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '';
            } catch (\JsonException) {
                return '';
            }
        }

        return (string) $value;
    }

    public function getValue(): mixed
    {
        if ($this->component) {
            $reflection = new \ReflectionClass($this->component);
            $docComment = $reflection->getDocComment();

            if ($docComment && $this->name) {
                $propertyName = ltrim($this->name, $this->component->getComponentId() . '__');
                if (preg_match('/@property\s+(\S+)\s+\$' . preg_quote($propertyName) . '\b/', $docComment, $matches)) {
                    $type = trim($matches[1]);

                    return $this->convertValueByType($this->value, $type);
                }
            }
        }

        return $this->value;
    }

    private function convertValueByType(mixed $value, string $type): mixed
    {
        if (str_contains($type, '|')) {
            $types = explode('|', $type);

            if (in_array('string', $types) && in_array('array', $types)) {
                return is_array($value) ? $value : (string)$value;
            }

            foreach ($types as $singleType) {
                if ($singleType !== 'null') {
                    return $this->convertValueByType($value, trim($singleType));
                }
            }
        }

        return match ($type) {
            'bool' => (bool)$value,
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            'array' => $this->convertToArray($value),
            default => $value,
        };
    }

    private function convertToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value === false || $value === null || $value === '') {
            return [];
        }

        return [$value];
    }
}
