<?php
declare(strict_types=1);

/**
 * Autenticación y control de acceso por roles.
 * Niveles: 1 Administrador, 2 Operador, 3 Cliente.
 */
class Auth
{
    public static function attempt(string $email, string $password): bool
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT u.*, r.codigo_rol, r.nombre_rol, r.nivel_acceso
               FROM usuarios u
               JOIN roles r ON r.id_rol = u.rol_id
              WHERE u.email = ? LIMIT 1',
            [$email]
        );

        if (!$user || $user['estado'] !== 'activo') {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $db->execute('UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?', [
                password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                $user['id_usuario'],
            ]);
        }

        session_regenerate_id(true);
        unset($user['password_hash']);
        $_SESSION['user'] = $user;
        $_SESSION['login_time'] = time();
        return true;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id_usuario']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['user']['id_usuario']) ? (int)$_SESSION['user']['id_usuario'] : null;
    }

    public static function isAdmin(): bool
    {
        return (int)($_SESSION['user']['nivel_acceso'] ?? 99) === 1;
    }

    public static function isStaff(): bool
    {
        return (int)($_SESSION['user']['nivel_acceso'] ?? 99) <= 2;
    }

    public static function isClient(): bool
    {
        return (int)($_SESSION['user']['nivel_acceso'] ?? 0) === 3;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('warning', 'Debes iniciar sesión para continuar.');
            redirect('login');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('dashboard');
        }
    }

    public static function requireStaff(): void
    {
        self::requireLogin();
        if (!self::isStaff()) {
            abort(403, 'Esta sección está reservada para administradores y operadores.');
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            abort(403, 'Esta acción requiere permisos de administrador.');
        }
    }

    public static function ownsRental(int $rentalUserId): bool
    {
        return self::id() === $rentalUserId || self::isStaff();
    }
}
