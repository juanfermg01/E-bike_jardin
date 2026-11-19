<?php
declare(strict_types=1);

class BikeController
{
    private array $estados = ['disponible', 'en_uso', 'mantenimiento', 'baja'];

    public function index(): void
    {
        $db = Database::getInstance();
        $q = trim((string)($_GET['q'] ?? ''));
        $estado = (string)($_GET['estado'] ?? '');
        $estacion = (int)($_GET['estacion'] ?? 0);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(b.codigo_qr LIKE ? OR b.modelo LIKE ? OR b.num_serie LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (in_array($estado, $this->estados, true)) {
            $where[] = 'b.estado = ?';
            $params[] = $estado;
        }
        if ($estacion > 0) {
            $where[] = 'b.estacion_act_fk = ?';
            $params[] = $estacion;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->scalar('SELECT COUNT(*) FROM bicicletas b' . $whereSql, $params);
        $pagination = paginate($total, 12, (int)($_GET['page'] ?? 1));

        $bikes = $db->fetchAll(
            'SELECT b.*, e.nombre AS estacion
               FROM bicicletas b
               LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk'
            . $whereSql .
            ' ORDER BY b.id_bicicleta DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');
        $resumen = $db->fetchAll('SELECT estado, COUNT(*) total FROM bicicletas GROUP BY estado');
        $porEstado = array_column($resumen, 'total', 'estado');

        view('bikes/index', [
            'pageTitle' => 'Bicicletas',
            'bikes' => $bikes,
            'stations' => $stations,
            'pagination' => $pagination,
            'porEstado' => $porEstado,
            'filters' => ['q' => $q, 'estado' => $estado, 'estacion' => $estacion],
            'assets' => ['qrcode' => Auth::isStaff()],
        ]);
    }

    public function create(): void
    {
        $db = Database::getInstance();
        $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');
        $nextId = (int)$db->scalar('SELECT COALESCE(MAX(id_bicicleta), 0) + 1 FROM bicicletas');

        view('bikes/form', [
            'pageTitle' => 'Nueva bicicleta',
            'bike' => [
                'codigo_qr' => 'EBJ-QR-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT),
                'num_serie' => '',
                'modelo' => '',
                'estacion_act_fk' => '',
                'estado' => 'disponible',
                'nivel_bateria' => '100',
                'kilometraje' => '0',
                'ult_manten_date' => '',
                'prox_manten_date' => '',
            ],
            'stations' => $stations,
            'errors' => [],
            'action' => url('bicicletas'),
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $data = $this->normalize($_POST);
        $errors = $this->validate($data);

        if (!isset($errors['codigo_qr']) && (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE codigo_qr = ?', [$data['codigo_qr']]) > 0) {
            $errors['codigo_qr'] = 'Ya existe una bicicleta con este código QR.';
        }
        if (!isset($errors['num_serie']) && (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE num_serie = ?', [$data['num_serie']]) > 0) {
            $errors['num_serie'] = 'Ya existe una bicicleta con este número de serie.';
        }

        if (!empty($errors)) {
            $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');
            view('bikes/form', [
                'pageTitle' => 'Nueva bicicleta',
                'bike' => $data,
                'stations' => $stations,
                'errors' => $errors,
                'action' => url('bicicletas'),
                'isEdit' => false,
            ]);
            return;
        }

        $db->execute(
            'INSERT INTO bicicletas (estacion_act_fk, codigo_qr, modelo, num_serie, nivel_bateria, estado, kilometraje, ult_manten_date, prox_manten_date)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['estacion_act_fk'] !== '' ? (int)$data['estacion_act_fk'] : null,
                $data['codigo_qr'],
                $data['modelo'],
                $data['num_serie'],
                $data['nivel_bateria'],
                $data['estado'],
                $data['kilometraje'],
                $data['ult_manten_date'] ?: null,
                $data['prox_manten_date'] ?: null,
            ]
        );

        flash('success', 'Bicicleta registrada correctamente.');
        redirect('bicicletas');
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ?', [(int)$id]);
        if (!$bike) {
            abort(404);
        }
        $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');

        view('bikes/form', [
            'pageTitle' => 'Editar bicicleta',
            'bike' => $bike,
            'stations' => $stations,
            'errors' => [],
            'action' => url('bicicletas/' . (int)$id),
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $db = Database::getInstance();
        $bikeId = (int)$id;
        $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ?', [$bikeId]);
        if (!$bike) {
            abort(404);
        }

        $data = $this->normalize($_POST);
        $errors = $this->validate($data);

        if (!isset($errors['codigo_qr']) && (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE codigo_qr = ? AND id_bicicleta <> ?', [$data['codigo_qr'], $bikeId]) > 0) {
            $errors['codigo_qr'] = 'Otra bicicleta ya usa este código QR.';
        }
        if (!isset($errors['num_serie']) && (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE num_serie = ? AND id_bicicleta <> ?', [$data['num_serie'], $bikeId]) > 0) {
            $errors['num_serie'] = 'Otra bicicleta ya usa este número de serie.';
        }

        if (!empty($errors)) {
            $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');
            view('bikes/form', [
                'pageTitle' => 'Editar bicicleta',
                'bike' => array_merge($bike, $data),
                'stations' => $stations,
                'errors' => $errors,
                'action' => url('bicicletas/' . $bikeId),
                'isEdit' => true,
            ]);
            return;
        }

        $db->execute(
            'UPDATE bicicletas SET estacion_act_fk = ?, codigo_qr = ?, modelo = ?, num_serie = ?, nivel_bateria = ?, estado = ?, kilometraje = ?, ult_manten_date = ?, prox_manten_date = ?
              WHERE id_bicicleta = ?',
            [
                $data['estacion_act_fk'] !== '' ? (int)$data['estacion_act_fk'] : null,
                $data['codigo_qr'],
                $data['modelo'],
                $data['num_serie'],
                $data['nivel_bateria'],
                $data['estado'],
                $data['kilometraje'],
                $data['ult_manten_date'] ?: null,
                $data['prox_manten_date'] ?: null,
                $bikeId,
            ]
        );

        flash('success', 'Bicicleta actualizada correctamente.');
        redirect('bicicletas');
    }

    public function destroy(string $id): void
    {
        $db = Database::getInstance();
        $bikeId = (int)$id;
        $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ?', [$bikeId]);
        if (!$bike) {
            abort(404);
        }

        $activeRentals = (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE id_bicicleta = ? AND estado = 'activo'", [$bikeId]);
        if ($activeRentals > 0) {
            flash('error', 'No puedes eliminar una bicicleta con un alquiler activo.');
            redirect('bicicletas');
        }

        try {
            $db->execute('DELETE FROM bicicletas WHERE id_bicicleta = ?', [$bikeId]);
            flash('success', 'Bicicleta eliminada del sistema.');
        } catch (PDOException $e) {
            flash('error', 'No se puede eliminar: la bicicleta tiene alquileres o mantenimientos asociados.');
        }
        redirect('bicicletas');
    }

    private function normalize(array $input): array
    {
        return [
            'codigo_qr' => trim((string)($input['codigo_qr'] ?? '')),
            'num_serie' => trim((string)($input['num_serie'] ?? '')),
            'modelo' => trim((string)($input['modelo'] ?? '')),
            'estacion_act_fk' => (string)($input['estacion_act_fk'] ?? ''),
            'estado' => (string)($input['estado'] ?? 'disponible'),
            'nivel_bateria' => (string)($input['nivel_bateria'] ?? '100'),
            'kilometraje' => (string)($input['kilometraje'] ?? '0'),
            'ult_manten_date' => (string)($input['ult_manten_date'] ?? ''),
            'prox_manten_date' => (string)($input['prox_manten_date'] ?? ''),
        ];
    }

    private function validate(array $data): array
    {
        $errors = Validator::validate($data, [
            'codigo_qr' => 'required|max:50',
            'num_serie' => 'required|max:100',
            'modelo' => 'required|max:100',
            'estado' => 'required|in:disponible,en_uso,mantenimiento,baja',
            'nivel_bateria' => 'required|numeric',
            'kilometraje' => 'required|numeric',
            'ult_manten_date' => 'date',
            'prox_manten_date' => 'date',
        ]);

        if (!isset($errors['nivel_bateria']) && ((float)$data['nivel_bateria'] < 0 || (float)$data['nivel_bateria'] > 100)) {
            $errors['nivel_bateria'] = 'La batería debe estar entre 0 y 100.';
        }
        if (!isset($errors['kilometraje']) && (float)$data['kilometraje'] < 0) {
            $errors['kilometraje'] = 'El kilometraje no puede ser negativo.';
        }
        if ($data['estacion_act_fk'] !== '' && !ctype_digit($data['estacion_act_fk'])) {
            $errors['estacion_act_fk'] = 'Selecciona una estación válida.';
        }
        return $errors;
    }
}
