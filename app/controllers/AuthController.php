<?php
declare(strict_types=1);

class AuthController
{
    public function showLogin(): void
    {
        view('auth/login', [
            'pageTitle' => 'Iniciar sesión',
            'old' => [],
            'errors' => [],
        ], 'layouts/guest');
    }

    public function login(): void
    {
        $data = [
            'email' => trim((string)($_POST['email'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
        ];

        $errors = Validator::validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!empty($errors)) {
            view('auth/login', ['pageTitle' => 'Iniciar sesión', 'old' => $data, 'errors' => $errors], 'layouts/guest');
            return;
        }

        $lockedUntil = (int)($_SESSION['login_locked_until'] ?? 0);
        if ($lockedUntil > time()) {
            $segundos = $lockedUntil - time();
            flash('error', "Demasiados intentos fallidos. Espera {$segundos} segundos e inténtalo de nuevo.");
            redirect('login');
        }

        if (!Auth::attempt($data['email'], $data['password'])) {
            $_SESSION['login_attempts'] = (int)($_SESSION['login_attempts'] ?? 0) + 1;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_locked_until'] = time() + 60;
                $_SESSION['login_attempts'] = 0;
                flash('error', 'Se bloquearon los intentos por 60 segundos. Verifica tus credenciales.');
            } else {
                flash('error', 'Correo o contraseña incorrectos, o el usuario está inactivo.');
            }
            redirect('login');
        }

        unset($_SESSION['login_attempts'], $_SESSION['login_locked_until']);
        flash('success', 'Bienvenido de nuevo, ' . explode(' ', (string)Auth::user()['nombre'])[0] . '.');
        redirect('dashboard');
    }

    public function showRegister(): void
    {
        view('auth/register', [
            'pageTitle' => 'Crear cuenta',
            'old' => [],
            'errors' => [],
        ], 'layouts/guest');
    }

    public function register(): void
    {
        $data = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'email' => trim((string)($_POST['email'] ?? '')),
            'telefono' => trim((string)($_POST['telefono'] ?? '')),
            'password' => (string)($_POST['password'] ?? ''),
            'password_confirm' => (string)($_POST['password_confirm'] ?? ''),
        ];

        $errors = Validator::validate($data, [
            'nombre' => 'required|min:3|max:100',
            'email' => 'required|email|max:150',
            'telefono' => 'max:20',
            'password' => 'required|min:8',
            'password_confirm' => 'required|min:8',
        ]);

        if ($data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden.';
        }

        $db = Database::getInstance();
        $existe = $db->scalar('SELECT COUNT(*) FROM usuarios WHERE email = ?', [$data['email']]);
        if ((int)$existe > 0) {
            $errors['email'] = 'Ya existe una cuenta registrada con este correo.';
        }

        if (!empty($errors)) {
            view('auth/register', ['pageTitle' => 'Crear cuenta', 'old' => $data, 'errors' => $errors], 'layouts/guest');
            return;
        }

        $rolId = (int)$db->scalar("SELECT id_rol FROM roles WHERE codigo_rol = 'CLIENTE' LIMIT 1");
        $db->execute(
            'INSERT INTO usuarios (rol_id, uuid, nombre, email, password_hash, telefono, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $rolId,
                uuid_v4(),
                $data['nombre'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['telefono'] !== '' ? $data['telefono'] : null,
                'activo',
            ]
        );

        Auth::attempt($data['email'], $data['password']);
        flash('success', 'Tu cuenta fue creada correctamente. ¡Bienvenido a E-Bike Jardín!');
        redirect('dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Tu sesión se cerró correctamente.');
        redirect('login');
    }
}
