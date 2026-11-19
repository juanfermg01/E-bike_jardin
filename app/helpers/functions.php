<?php
declare(strict_types=1);

/**
 * Funciones auxiliares usadas por las vistas y los controladores.
 */

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function view(string $view, array $data = [], string $layout = 'layouts/app'): void
{
    extract($data, EXTR_SKIP);
    $viewFile = APP_PATH . '/views/' . $view . '.php';
    $layoutFile = APP_PATH . '/views/' . $layout . '.php';

    if (!is_file($viewFile)) {
        http_response_code(500);
        exit('Vista no encontrada: ' . e($view));
    }

    ob_start();
    require $viewFile;
    $content = (string)ob_get_clean();
    require $layoutFile;
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['_csrf'] ?? '';
    return is_string($token) && $token !== '' && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function current_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = parse_url(BASE_URL, PHP_URL_PATH) ?: '';
    if ($base !== '' && str_starts_with($path, $base)) {
        $path = substr($path, strlen($base));
    }
    return trim($path, '/');
}

function is_active(string $prefix): string
{
    $current = current_path();
    return ($current === $prefix || str_starts_with($current, $prefix . '/')) ? 'active' : '';
}

function format_money(float|int|string|null $amount): string
{
    return '$ ' . number_format((float)$amount, 0, ',', '.') . ' ' . MONEDA;
}

function format_date(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (empty($date)) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '—';
}

function format_minutes(?int $minutes): string
{
    if ($minutes === null) {
        return '—';
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return $h > 0 ? "{$h}h {$m}min" : "{$m} min";
}

function badge_estado(string $estado): string
{
    $map = [
        'activo' => 'success', 'completado' => 'primary', 'cancelado' => 'secondary', 'incidencia' => 'danger',
        'disponible' => 'success', 'en_uso' => 'warning', 'mantenimiento' => 'info', 'baja' => 'dark',
        'operativa' => 'success', 'inactiva' => 'secondary',
        'activa' => 'success', 'inactivo' => 'secondary', 'suspendido' => 'danger',
        'aprobado' => 'success', 'pendiente' => 'warning', 'rechazado' => 'danger', 'reembolsado' => 'info',
    ];
    $color = $map[$estado] ?? 'secondary';
    $label = str_replace('_', ' ', $estado);
    return '<span class="badge text-bg-' . $color . ' badge-soft">' . e(ucfirst($label)) . '</span>';
}

function old(array $data, string $key, mixed $default = ''): string
{
    return e($data[$key] ?? $default);
}

function strftime_es(): string
{
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[(int)date('w')] . ', ' . date('d') . ' de ' . $meses[(int)date('n') - 1] . ' de ' . date('Y');
}

function uuid_v4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function paginate(int $total, int $perPage, int $currentPage): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $currentPage = min(max(1, $currentPage), $pages);
    return [
        'total' => $total,
        'per_page' => $perPage,
        'page' => $currentPage,
        'pages' => $pages,
        'offset' => ($currentPage - 1) * $perPage,
    ];
}

function render_pagination(array $pagination, string $baseUrl, array $query = []): void
{
    if ($pagination['pages'] <= 1) {
        return;
    }
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    unset($query['page']);
    $queryString = http_build_query($query);
    $prefix = $queryString ? '?' . $queryString . '&page=' : '?page=';

    echo '<nav class="mt-3"><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $pagination['pages']; $i++) {
        if ($pagination['pages'] > 9 && $i > 2 && $i < $pagination['pages'] - 1 && abs($i - $pagination['page']) > 1) {
            if ($i === 3 || $i === $pagination['pages'] - 2) {
                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            }
            continue;
        }
        $active = $i === $pagination['page'] ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . e(url($baseUrl) . $prefix . $i) . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [
        403 => 'Acceso denegado',
        404 => 'Página no encontrada',
        405 => 'Método no permitido',
        419 => 'Sesión expirada',
        500 => 'Error interno',
    ];
    $layout = class_exists('Auth') && Auth::check() ? 'layouts/app' : 'layouts/guest';
    $data = [
        'pageTitle' => $titles[$code] ?? 'Error',
        'code' => $code,
        'message' => $message !== '' ? $message : ($titles[$code] ?? 'Ha ocurrido un error'),
    ];
    view('errors/error', $data, $layout);
    exit;
}
// 
function log_exception(Throwable $e): void
{
    $dir = STORAGE_PATH . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage()
        . ' en ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL;
    @file_put_contents($dir . '/app.log', $line . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
}
