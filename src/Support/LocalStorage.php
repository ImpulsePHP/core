<?php

declare(strict_types=1);

namespace Impulse\Core\Support;

use Impulse\Core\Http\Response;

final class LocalStorage
{
    private const MAX_PAYLOAD_SIZE = 1048576; // 1MB
    private static ?array $globalParsedData = null;

    public static function getGlobalData(): array
    {
        if (self::$globalParsedData === null) {
            self::$globalParsedData = self::parseOnce();
        }

        return self::$globalParsedData;
    }

    private static function parseOnce(): array
    {
        return $_POST['_local_storage'] ?? [];
    }

    /**
     * @throws \JsonException
     */
    public static function ingestRequestPayload(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $isLocalStorageSyncRequest = isset($_SERVER['HTTP_X_LOCALSTORAGE_SYNC']) &&
            $_SERVER['HTTP_X_LOCALSTORAGE_SYNC'] === '1';

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isJsonRequest = str_starts_with($contentType, 'application/json');

        if ($isJsonRequest) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                if (strlen($raw) > self::MAX_PAYLOAD_SIZE) {
                    DevError::respond('Charge utile LocalStorage trop volumineuse (ignorée)');
                }
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($data) && isset($data['_local_storage'])) {
                    $localStorage = $data['_local_storage'];
                    $metadata = $data['_metadata'] ?? [];
                    $currentData = $_SESSION['_local_storage'] ?? [];

                    if (empty($currentData)) {
                        $_SESSION['_local_storage'] = $localStorage;
                    } else {
                        if (!empty($metadata)) {
                            if (!empty($metadata['force_refresh'])) {
                                $_SESSION['_local_storage'] = $localStorage;
                            } else {
                                $deletedKeys = $metadata['deleted_keys'] ?? [];
                                foreach ($deletedKeys as $key) {
                                    unset($currentData[$key]);
                                }

                                $_SESSION['_local_storage'] = array_merge($currentData, $localStorage);
                            }
                        } else {
                            $_SESSION['_local_storage'] = array_merge($currentData, $localStorage);
                        }
                    }

                    $_POST['_local_storage'] = $_SESSION['_local_storage'];

                    if (isset($metadata['timestamp'])) {
                        $_SESSION['_local_storage_last_update'] = $metadata['timestamp'];
                    }

                    if ($isLocalStorageSyncRequest) {
                        if (!empty($metadata['force_refresh'])) {
                            Response::json([
                                'success' => true,
                                'timestamp' => $_SESSION['_local_storage_last_update'] ?? time() * 1000
                            ])->send();
                        } else {
                            Response::noContent()->send();
                        }
                        exit;
                    }
                }
            }
        }

        if (!isset($_POST['_local_storage']) && isset($_SESSION['_local_storage'])) {
            $_POST['_local_storage'] = $_SESSION['_local_storage'];
        }
    }

    public static function clearSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['_local_storage'])) {
            unset($_SESSION['_local_storage']);
        }

        if (isset($_SESSION['_local_storage_last_update'])) {
            unset($_SESSION['_local_storage_last_update']);
        }
    }

    public static function syncFromSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['_local_storage'])) {
            $_POST['_local_storage'] = $_SESSION['_local_storage'];
        }
    }
}
