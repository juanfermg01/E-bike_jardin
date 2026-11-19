<?php
declare(strict_types=1);

class StationController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $stations = $db->fetchAll('SELECT * FROM v_estaciones_mapa ORDER BY nombre');
        $totals = $db->fetch(
            "SELECT COUNT(*) total, COALESCE(SUM(bicis_disponibles),0) disponibles, COALESCE(SUM(bicis_totales),0) flota,
                    COALESCE(SUM(energia_disp),0) energia
               FROM v_estaciones_mapa"
        );

        view('stations/index', [
            'pageTitle' => 'Estaciones',
            'stations' => $stations,
            'totals' => $totals,
            'isAdmin' => Auth::isAdmin(),
        ]);
    }

    public function create(): void
    {
        view('stations/form', [
            'pageTitle' => 'Nueva estación',
            'station' => [
                'codigo' => 'EST-' . str_pad((string)((int)Database::getInstance()->scalar('SELECT COALESCE(MAX(id_estacion),0)+1 FROM estaciones')), 2, '0', STR_PAD_LEFT),
                'nombre' => '',
                'direccion' => '',
                'latitud' => '5.5986',
                'longitud' => '-75.8199',
                'capacidad' => '10',
                'energia_disp' => '100',
                'estado' => 'operativa',
            ],
            'errors' => [],
            'action' => url('estaciones'),
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $data = $this->normalize($_POST);
        $errors = $this->validate($data);

        if (!isset($errors['codigo']) && (int)$db->scalar('SELECT COUNT(*) FROM estaciones WHERE codigo = ?', [$data['codigo']]) > 0) {
            $errors['codigo'] = 'Ya existe una estación con este código.';
        }

        if (!empty($errors)) {
            view('stations/form', ['pageTitle' => 'Nueva estación', 'station' => $data, 'errors' => $errors, 'action' => url('estaciones'), 'isEdit' => false]);
            return;
        }

        $point = sprintf('POINT(%.7F %.7F)', (float)$data['longitud'], (float)$data['latitud']);
        $db->execute(
            'INSERT INTO estaciones (codigo, nombre, direccion, coordenadas, capacidad, energia_disp, estado)
             VALUES (?, ?, ?, ST_GeomFromText(?), ?, ?, ?)',
            [$data['codigo'], $data['nombre'], $data['direccion'], $point, (int)$data['capacidad'], (float)$data['energia_disp'], $data['estado']]
        );

        flash('success', 'Estación creada correctamente.');
        redirect('estaciones');
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $station = $db->fetch('SELECT *, ST_Y(coordenadas) latitud, ST_X(coordenadas) longitud FROM estaciones WHERE id_estacion = ?', [(int)$id]);
        if (!$station) {
            abort(404);
        }
        view('stations/form', [
            'pageTitle' => 'Editar estación',
            'station' => $station,
            'errors' => [],
            'action' => url('estaciones/' . (int)$id),
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $db = Database::getInstance();
        $stationId = (int)$id;
        $station = $db->fetch('SELECT * FROM estaciones WHERE id_estacion = ?', [$stationId]);
        if (!$station) {
            abort(404);
        }

        $data = $this->normalize($_POST);
        $errors = $this->validate($data);
        if (!isset($errors['codigo']) && (int)$db->scalar('SELECT COUNT(*) FROM estaciones WHERE codigo = ? AND id_estacion <> ?', [$data['codigo'], $stationId]) > 0) {
            $errors['codigo'] = 'Otra estación ya usa este código.';
        }

        if (!empty($errors)) {
            view('stations/form', [
                'pageTitle' => 'Editar estación',
                'station' => array_merge($station, $data),
                'errors' => $errors,
                'action' => url('estaciones/' . $stationId),
                'isEdit' => true,
            ]);
            return;
        }

        $point = sprintf('POINT(%.7F %.7F)', (float)$data['longitud'], (float)$data['latitud']);
        $db->execute(
            'UPDATE estaciones SET codigo = ?, nombre = ?, direccion = ?, coordenadas = ST_GeomFromText(?), capacidad = ?, energia_disp = ?, estado = ?
              WHERE id_estacion = ?',
            [$data['codigo'], $data['nombre'], $data['direccion'], $point, (int)$data['capacidad'], (float)$data['energia_disp'], $data['estado'], $stationId]
        );

        flash('success', 'Estación actualizada correctamente.');
        redirect('estaciones');
    }

    public function destroy(string $id): void
    {
        $db = Database::getInstance();
        $stationId = (int)$id;
        $station = $db->fetch('SELECT * FROM estaciones WHERE id_estacion = ?', [$stationId]);
        if (!$station) {
            abort(404);
        }

        $bikes = (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE estacion_act_fk = ?', [$stationId]);
        if ($bikes > 0) {
            flash('error', "No puedes eliminar la estación: tiene {$bikes} bicicletas asignadas. Reubícalas primero.");
            redirect('estaciones');
        }

        try {
            $db->execute('DELETE FROM estaciones WHERE id_estacion = ?', [$stationId]);
            flash('success', 'Estación eliminada correctamente.');
        } catch (PDOException $e) {
            flash('error', 'No se puede eliminar: la estación tiene alquileres o registros de energía asociados.');
        }
        redirect('estaciones');
    }

    private function normalize(array $input): array
    {
        return [
            'codigo' => trim((string)($input['codigo'] ?? '')),
            'nombre' => trim((string)($input['nombre'] ?? '')),
            'direccion' => trim((string)($input['direccion'] ?? '')),
            'latitud' => trim((string)($input['latitud'] ?? '')),
            'longitud' => trim((string)($input['longitud'] ?? '')),
            'capacidad' => trim((string)($input['capacidad'] ?? '')),
            'energia_disp' => trim((string)($input['energia_disp'] ?? '')),
            'estado' => (string)($input['estado'] ?? 'operativa'),
        ];
    }

    private function validate(array $data): array
    {
        $errors = Validator::validate($data, [
            'codigo' => 'required|max:20',
            'nombre' => 'required|max:100',
            'direccion' => 'max:255',
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'capacidad' => 'required|int',
            'energia_disp' => 'required|numeric',
            'estado' => 'required|in:operativa,mantenimiento,inactiva',
        ]);

        if (!isset($errors['latitud']) && ((float)$data['latitud'] < -90 || (float)$data['latitud'] > 90)) {
            $errors['latitud'] = 'La latitud debe estar entre -90 y 90.';
        }
        if (!isset($errors['longitud']) && ((float)$data['longitud'] < -180 || (float)$data['longitud'] > 180)) {
            $errors['longitud'] = 'La longitud debe estar entre -180 y 180.';
        }
        if (!isset($errors['capacidad']) && (int)$data['capacidad'] <= 0) {
            $errors['capacidad'] = 'La capacidad debe ser mayor que cero.';
        }
        if (!isset($errors['energia_disp']) && ((float)$data['energia_disp'] < 0 || (float)$data['energia_disp'] > 100)) {
            $errors['energia_disp'] = 'La energía debe estar entre 0 y 100.';
        }
        return $errors;
    }
}
