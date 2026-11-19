<?php
declare(strict_types=1);

class ProfileController
{
    public function show(): void
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT u.*, r.nombre_rol, r.codigo_rol, r.nivel_acceso
               FROM usuarios u JOIN roles r ON r.id_rol = u.rol_id
              WHERE u.id_usuario = ?',
            [Auth::id()]
        );
        if (!$user) {
            Auth::logout();
            redirect('login');
        }
        unset($user['password_hash']);

        $stats = [
            'alquileres' => (int)$db->scalar('SELECT COUNT(*) FROM alquileres WHERE id_usuario = ?', [Auth::id()]),
            'completados' => (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE id_usuario = ? AND estado = 'completado'", [Auth::id()]),
            'gastado' => (float)$db->scalar(
                "SELECT COALESCE(SUM(t.monto),0) FROM transacciones t
                   JOIN alquileres a ON a.id_alquiler = t.id_alquiler_fk
                  WHERE a.id_usuario = ? AND t.estado_pago = 'aprobado'",
                [Auth::id()]
            ),
            'metodos' => (int)$db->scalar('SELECT COUNT(*) FROM metodos_pago WHERE id_usuario = ? AND activo = 1', [Auth::id()]),
        ];

        $metodos = $db->fetchAll(
            'SELECT tipo, ultimos_digitos, alias, fecha_creacion FROM metodos_pago
              WHERE id_usuario = ? AND activo = 1 ORDER BY fecha_creacion DESC',
            [Auth::id()]
        );

        view('profile/index', [
            'pageTitle' => 'Mi perfil',
            'user' => $user,
            'stats' => $stats,
            'metodos' => $metodos,
            'errors' => [],
            'pwdErrors' => [],
        ]);
    }

    public function update(): void
    {
        $db = Database::getInstance();
        $data = [
            'nombre' => trim((string)($_POST['nombre'] ?? '')),
            'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
            'telefono' => trim((string)($_POST['telefono'] ?? '')),
        ];

        $errors = Validator::validate($data, [
            'nombre' => 'required|max:100',
            'email' => 'required|email|max:150',
            'telefono' => 'max:20',
        ]);
        if (!isset($errors['email']) && (int)$db->scalar('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id_usuario <> ?', [$data['email'], Auth::id()]) > 0) {
            $errors['email'] = 'Ese correo ya está registrado por otra cuenta.';
        }

        if (!empty($errors)) {
            $this->renderWithErrors($errors, []);
            return;
        }

        $db->execute(
            'UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id_usuario = ?',
            [$data['nombre'], $data['email'], $data['telefono'] ?: null, Auth::id()]
        );
        $_SESSION['user']['nombre'] = $data['nombre'];
        $_SESSION['user']['email'] = $data['email'];
        $_SESSION['user']['telefono'] = $data['telefono'];

        flash('success', 'Datos actualizados correctamente.');
        redirect('perfil');
    }

    public function changePassword(): void
    {
        $db = Database::getInstance();
        $current = (string)($_POST['current_password'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');

        $errors = Validator::validate(
            ['current_password' => $current, 'password' => $password, 'password_confirm' => $confirm],
            ['current_password' => 'required', 'password' => 'required|min:8', 'password_confirm' => 'required']
        );

        $user = $db->fetch('SELECT password_hash FROM usuarios WHERE id_usuario = ?', [Auth::id()]);
        if (!isset($errors['current_password']) && (!$user || !password_verify($current, $user['password_hash']))) {
            $errors['current_password'] = 'La contraseña actual no es correcta.';
        }
        if (!isset($errors['password_confirm']) && $password !== $confirm) {
            $errors['password_confirm'] = 'Las contraseñas nuevas no coinciden.';
        }
        if (!isset($errors['password']) && $password !== '' && $password === $current) {
            $errors['password'] = 'La nueva contraseña debe ser diferente a la actual.';
        }

        if (!empty($errors)) {
            $this->renderWithErrors([], $errors);
            return;
        }

        $db->execute(
            'UPDATE usuarios SET password_hash = ? WHERE id_usuario = ?',
            [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), Auth::id()]
        );

        flash('success', 'Contraseña actualizada. Úsala en tu próximo inicio de sesión.');
        redirect('perfil');
    }

    private function renderWithErrors(array $errors, array $pwdErrors): void
    {
        $db = Database::getInstance();
        $user = $db->fetch(
            'SELECT u.*, r.nombre_rol, r.codigo_rol, r.nivel_acceso
               FROM usuarios u JOIN roles r ON r.id_rol = u.rol_id
              WHERE u.id_usuario = ?',
            [Auth::id()]
        );
        unset($user['password_hash']);

        $stats = [
            'alquileres' => (int)$db->scalar('SELECT COUNT(*) FROM alquileres WHERE id_usuario = ?', [Auth::id()]),
            'completados' => (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE id_usuario = ? AND estado = 'completado'", [Auth::id()]),
            'gastado' => (float)$db->scalar(
                "SELECT COALESCE(SUM(t.monto),0) FROM transacciones t
                   JOIN alquileres a ON a.id_alquiler = t.id_alquiler_fk
                  WHERE a.id_usuario = ? AND t.estado_pago = 'aprobado'",
                [Auth::id()]
            ),
            'metodos' => (int)$db->scalar('SELECT COUNT(*) FROM metodos_pago WHERE id_usuario = ? AND activo = 1', [Auth::id()]),
        ];
        $metodos = $db->fetchAll(
            'SELECT tipo, ultimos_digitos, alias, fecha_creacion FROM metodos_pago
              WHERE id_usuario = ? AND activo = 1 ORDER BY fecha_creacion DESC',
            [Auth::id()]
        );

        view('profile/index', [
            'pageTitle' => 'Mi perfil',
            'user' => $user,
            'stats' => $stats,
            'metodos' => $metodos,
            'errors' => $errors,
            'pwdErrors' => $pwdErrors,
        ]);
    }
}
