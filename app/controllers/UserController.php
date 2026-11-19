<?php
declare(strict_types=1);

class UserController
{
    private array $estados = ['activo', 'inactivo', 'suspendido'];

    public function index(): void
    {
        $db = Database::getInstance();
        $q = trim((string)($_GET['q'] ?? ''));
        $rol = (int)($_GET['rol'] ?? 0);
        $estado = (string)($_GET['estado'] ?? '');

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(u.nombre LIKE ? OR u.email LIKE ? OR u.telefono LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if ($rol > 0) {
            $where[] = 'u.rol_id = ?';
            $params[] = $rol;
        }
        if (in_array($estado, $this->estados, true)) {
            $where[] = 'u.estado = ?';
            $params[] = $estado;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->scalar('SELECT COUNT(*) FROM usuarios u' . $whereSql, $params);
        $pagination = paginate($total, 12, (int)($_GET['page'] ?? 1));

        $users = $db->fetchAll(
            'SELECT u.id_usuario, u.nombre, u.email, u.telefono, u.estado, u.fecha_registro,
                    r.id_rol, r.nombre_rol, r.nivel_acceso,
                    (SELECT COUNT(*) FROM alquileres a WHERE a.id_usuario = u.id_usuario) AS total_alquileres
               FROM usuarios u
               JOIN roles r ON r.id_rol = u.rol_id'
            . $whereSql .
            ' ORDER BY u.id_usuario ASC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $roles = $db->fetchAll('SELECT * FROM roles ORDER BY nivel_acceso');
        $resumen = $db->fetchAll('SELECT estado, COUNT(*) total FROM usuarios GROUP BY estado');
        $porEstado = array_column($resumen, 'total', 'estado');
        $totalPorRol = $db->fetchAll('SELECT r.nombre_rol, COUNT(*) total FROM usuarios u JOIN roles r ON r.id_rol = u.rol_id GROUP BY r.id_rol ORDER BY r.nivel_acceso');

        view('users/index', [
            'pageTitle' => 'Usuarios',
            'users' => $users,
            'roles' => $roles,
            'totalPorRol' => $totalPorRol,
            'porEstado' => $porEstado,
            'pagination' => $pagination,
            'filters' => ['q' => $q, 'rol' => $rol, 'estado' => $estado],
        ]);
    }

    public function create(): void
    {
        $db = Database::getInstance();
        view('users/form', [
            'pageTitle' => 'Nuevo usuario',
            'user' => [
                'nombre' => '',
                'email' => '',
                'telefono' => '',
                'rol_id' => '3',
                'estado' => 'activo',
            ],
            'roles' => $db->fetchAll('SELECT * FROM roles ORDER BY nivel_acceso'),
            'errors' => [],
            'action' => url('usuarios'),
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $data = $this->normalize($_POST);
        $errors = $this->validate($data, true);

        if (!isset($errors['email']) && (int)$db->scalar('SELECT COUNT(*) FROM usuarios WHERE email = ?', [$data['email']]) > 0) {
            $errors['email'] = 'Ya existe un usuario registrado con este correo.';
        }
        if (!isset($errors['rol_id']) && (int)$db->scalar('SELECT COUNT(*) FROM roles WHERE id_rol = ?', [(int)$data['rol_id']]) === 0) {
            $errors['rol_id'] = 'El rol seleccionado no existe.';
        }

        if (!empty($errors)) {
            view('users/form', [
                'pageTitle' => 'Nuevo usuario',
                'user' => $data,
                'roles' => $db->fetchAll('SELECT * FROM roles ORDER BY nivel_acceso'),
                'errors' => $errors,
                'action' => url('usuarios'),
                'isEdit' => false,
            ]);
            return;
        }

        $db->execute(
            'INSERT INTO usuarios (rol_id, uuid, nombre, email, password_hash, telefono, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (int)$data['rol_id'],
                uuid_v4(),
                $data['nombre'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['telefono'] ?: null,
                $data['estado'],
            ]
        );

        flash('success', 'Usuario creado correctamente.');
        redirect('usuarios');
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $user = $db->fetch('SELECT * FROM usuarios WHERE id_usuario = ?', [(int)$id]);
        if (!$user) {
            abort(404);
        }

        view('users/form', [
            'pageTitle' => 'Editar usuario',
            'user' => $user,
            'roles' => $db->fetchAll('SELECT * FROM roles ORDER BY nivel_acceso'),
            'errors' => [],
            'action' => url('usuarios/' . (int)$id),
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $db = Database::getInstance();
        $userId = (int)$id;
        $user = $db->fetch('SELECT * FROM usuarios WHERE id_usuario = ?', [$userId]);
        if (!$user) {
            abort(404);
        }

        $data = $this->normalize($_POST);
        $errors = $this->validate($data, false);

        if (!isset($errors['email']) && (int)$db->scalar('SELECT COUNT(*) FROM usuarios WHERE email = ? AND id_usuario <> ?', [$data['email'], $userId]) > 0) {
            $errors['email'] = 'Otro usuario ya usa este correo electrónico.';
        }
        if (!isset($errors['rol_id']) && (int)$db->scalar('SELECT COUNT(*) FROM roles WHERE id_rol = ?', [(int)$data['rol_id']]) === 0) {
            $errors['rol_id'] = 'El rol seleccionado no existe.';
        }
        if ($userId === Auth::id() && (int)$data['rol_id'] !== (int)$user['rol_id']) {
            $errors['rol_id'] = 'No puedes cambiar tu propio rol.';
        }
        if ($userId === Auth::id() && $data['estado'] !== 'activo') {
            $errors['estado'] = 'No puedes desactivar tu propia cuenta.';
        }

        if (!empty($errors)) {
            view('users/form', [
                'pageTitle' => 'Editar usuario',
                'user' => array_merge($user, $data),
                'roles' => $db->fetchAll('SELECT * FROM roles ORDER BY nivel_acceso'),
                'errors' => $errors,
                'action' => url('usuarios/' . $userId),
                'isEdit' => true,
            ]);
            return;
        }

        $sql = 'UPDATE usuarios SET rol_id = ?, nombre = ?, email = ?, telefono = ?, estado = ?';
        $params = [(int)$data['rol_id'], $data['nombre'], $data['email'], $data['telefono'] ?: null, $data['estado']];

        if ($data['password'] !== '') {
            $sql .= ', password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $sql .= ' WHERE id_usuario = ?';
        $params[] = $userId;

        $db->execute($sql, $params);

        if ($userId === Auth::id()) {
            $_SESSION['user']['nombre'] = $data['nombre'];
            $_SESSION['user']['email'] = $data['email'];
        }

        flash('success', 'Usuario actualizado correctamente.');
        redirect('usuarios');
    }

    public function destroy(string $id): void
    {
        $db = Database::getInstance();
        $userId = (int)$id;
        $user = $db->fetch('SELECT * FROM usuarios WHERE id_usuario = ?', [$userId]);
        if (!$user) {
            abort(404);
        }
        if ($userId === Auth::id()) {
            flash('error', 'No puedes eliminar tu propia cuenta mientras tienes la sesión activa.');
            redirect('usuarios');
        }

        $rentals = (int)$db->scalar('SELECT COUNT(*) FROM alquileres WHERE id_usuario = ?', [$userId]);
        if ($rentals > 0) {
            flash('error', "No puedes eliminar a {$user['nombre']}: tiene {$rentals} alquileres registrados. Cambia su estado a inactivo.");
            redirect('usuarios');
        }

        try {
            $db->execute('DELETE FROM usuarios WHERE id_usuario = ?', [$userId]);
            flash('success', 'Usuario eliminado correctamente.');
        } catch (PDOException $e) {
            flash('error', 'No se pudo eliminar el usuario porque tiene registros asociados.');
        }
        redirect('usuarios');
    }

    private function normalize(array $input): array
    {
        return [
            'nombre' => trim((string)($input['nombre'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'telefono' => trim((string)($input['telefono'] ?? '')),
            'rol_id' => (string)($input['rol_id'] ?? ''),
            'estado' => (string)($input['estado'] ?? 'activo'),
            'password' => (string)($input['password'] ?? ''),
            'password_confirm' => (string)($input['password_confirm'] ?? ''),
        ];
    }

    private function validate(array $data, bool $requirePassword): array
    {
        $rules = [
            'nombre' => 'required|max:100',
            'email' => 'required|email|max:150',
            'telefono' => 'max:20',
            'rol_id' => 'required|int',
            'estado' => 'required|in:activo,inactivo,suspendido',
        ];
        if ($requirePassword) {
            $rules['password'] = 'required|min:8';
            $rules['password_confirm'] = 'required';
        } elseif ($data['password'] !== '') {
            $rules['password'] = 'min:8';
        } else {
            unset($rules['password'], $rules['password_confirm']);
        }

        $errors = Validator::validate($data, $rules);

        if (isset($rules['password_confirm']) && !isset($errors['password_confirm']) && $data['password'] !== $data['password_confirm']) {
            $errors['password_confirm'] = 'Las contraseñas no coinciden.';
        }
        return $errors;
    }
}
