<?php
declare(strict_types=1);

/**
 * Enrutador sencillo: asocia método + ruta con un controlador.
 * Las rutas con {param} se convierten en expresiones regulares.
 */
class Router
{
    private array $routes = [];

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, string $handler, array $middleware): void
    {
        $normalized = trim($path, '/');
        $pattern = $normalized === '' ? '' : preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $normalized);
        $this->routes[] = [
            'method' => $method,
            'path' => $normalized,
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = trim((string)parse_url($uri, PHP_URL_PATH), '/');
        $base = trim((string)parse_url(BASE_URL, PHP_URL_PATH), '/');

        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = trim(substr($path, strlen($base)), '/');
        }

        // Compatibilidad con index.php?url=ruta cuando no hay mod_rewrite.
        if ($path === 'index.php') {
            $path = trim((string)($_GET['url'] ?? ''), '/');
        }

        $methodMatched = false;

        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $methodMatched = true;
                continue;
            }

            foreach ($route['middleware'] as $middleware) {
                $this->runMiddleware($middleware);
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            [$controllerName, $action] = explode('@', $route['handler']);
            if (!class_exists($controllerName)) {
                abort(500, 'Controlador no encontrado: ' . $controllerName);
            }
            $controller = new $controllerName();
            if (!method_exists($controller, $action)) {
                abort(500, 'Acción no encontrada: ' . $action);
            }
            $controller->{$action}(...array_values($params));
            return;
        }

        if ($methodMatched) {
            abort(405, 'Método HTTP no permitido para esta ruta.');
        }
        abort(404);
    }

    private function runMiddleware(string $middleware): void
    {
        match ($middleware) {
            'auth' => Auth::requireLogin(),
            'guest' => Auth::requireGuest(),
            'staff' => Auth::requireStaff(),
            'admin' => Auth::requireAdmin(),
            default => null,
        };
    }
}
