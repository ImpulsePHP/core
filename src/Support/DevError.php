<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

use JetBrains\PhpStorm\NoReturn;

final class DevError
{
    /**
     * @throws \JsonException
     */
    #[NoReturn]
    public static function respond(string $message, int $httpCode = 400, ?string $errorCode = null): void
    {
        if (Config::get('env', 'prod') !== 'dev') {
            Logger::error($message, [
                'class' => self::class,
                'method' => __METHOD__,
            ]);

            http_response_code($httpCode);
            exit;
        }

        http_response_code($httpCode);
        header('Content-Type: application/json');
        $payload = [
            'error' => true,
            'message' => $message,
        ];
        if ($errorCode !== null) {
            $payload['code'] = $errorCode;
        }
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        Logger::error($message, [
            'class' => self::class,
            'method' => __METHOD__,
        ]);

        exit;
    }
}
