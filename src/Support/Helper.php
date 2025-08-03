<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

final class Helper
{
    public static function isValidateJson(string $content): bool
    {
        $trimmed = trim($content);

        if ($trimmed === '' || ($trimmed[0] !== '{' && $trimmed[0] !== '[')) {
            return false;
        }

        try {
            json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    public static function containsHtml(string $value): bool
    {
        return $value !== strip_tags($value) ||
            preg_match('/<[a-z][^>]*>/i', $value) ||
            preg_match('/<\/[a-z]+>/i', $value);
    }
}
