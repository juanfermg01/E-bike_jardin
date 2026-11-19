<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/config/config.php';
require APP_PATH . '/helpers/functions.php';

spl_autoload_register(function (string $class): void {
    foreach (['core', 'helpers', 'controllers', 'models', 'middleware'] as $folder) {
        $file = APP_PATH . '/' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

ini_set('display_errors', '0');
error_reporting(E_ALL);

set_exception_handler(function (Throwable $e): void {
    log_exception($e);
    if (APP_DEBUG) {
        http_response_code(500);
        echo '<div style="font-family:monospace;padding:2rem;background:#111;color:#f66">';
        echo '<h2>Error de desarrollo</h2><p>' . e($e->getMessage()) . '</p>';
        echo '<p>' . e($e->getFile()) . ':' . $e->getLine() . '</p>';
        echo '<pre style="white-space:pre-wrap;color:#ddd">' . e($e->getTraceAsString()) . '</pre></div>';
        exit;
    }
    abort(500, 'No fue posible procesar la solicitud. Revisa el registro de errores.');
});

session_name('EBIKEJARDIN');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
    abort(419, 'La sesión expiró o el formulario no es válido. Recarga la página e inténtalo de nuevo.');
}

$router = new Router();
require APP_PATH . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
