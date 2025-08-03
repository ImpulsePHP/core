<?php

declare(strict_types=1);

namespace Impulse\Core\Contracts;

interface StateInterface
{
    public function get(): mixed;
    public function set(mixed $value): void;
    public function isProtected(): bool;
    public function getForDom(): mixed;
    public static function decrypt(string $encryptedData): mixed;
    public function __toString(): string;
}
