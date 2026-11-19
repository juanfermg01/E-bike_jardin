<?php
declare(strict_types=1);

// Enrutador para el servidor embebido: php -S localhost:8080 -t public public/router.php
$uri = urldecode((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
