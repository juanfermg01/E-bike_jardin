<?php
declare(strict_types=1);

/**
 * Configuración general de E-Bike Jardín.
 * Ajusta únicamente las credenciales de la base de datos si tu MySQL usa otra clave.
 */

define('APP_NAME', 'E-Bike Jardín');
define('APP_VERSION', '1.0.0');
define('APP_PATH', dirname(__DIR__));
define('BASE_PATH', dirname(APP_PATH));
define('STORAGE_PATH', BASE_PATH . '/storage');
define('APP_DEBUG', getenv('APP_DEBUG') === '1');

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ebike_jardin');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

define('TARIFA_POR_MINUTO', 150.00);
define('MONEDA', 'COP');
define('APP_TIMEZONE', 'America/Bogota');

date_default_timezone_set(APP_TIMEZONE);

/**
 * Detecta automáticamente la carpeta pública del proyecto.
 * Funciona con el servidor embebido de PHP y con Apache/XAMPP en subcarpetas.
 */
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$autoBase = rtrim(str_replace('/index.php', '', $scriptName), '/');
define('BASE_URL', getenv('APP_BASE_URL') !== false ? rtrim((string)getenv('APP_BASE_URL'), '/') : $autoBase);
