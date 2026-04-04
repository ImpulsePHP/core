<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$translationAutoload = dirname(__DIR__, 2) . '/translation/vendor/autoload.php';
if (is_file($translationAutoload)) {
    require_once $translationAutoload;
}

