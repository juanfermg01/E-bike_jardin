<?php
declare(strict_types=1);

class EnergyController
{
    private array $tipos = ['Recarga de baterías', 'Consumo estación', 'Compra de energía', 'Mantenimiento eléctrico'];

    public function index(): void
    {
        $db = Database::getInstance();
        $stationId = (int)($_GET['estacion'] ?? 0);
        $tipo = trim((string)($_GET['tipo'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        $where = [];
        $params = [];
        if ($stationId > 0) {
            $where[] = 'r.id_estacion_fk = ?';
            $params[] = $stationId;
        }
        if ($tipo !== '') {
            $where[] = 'r.tipo_operac = ?';
            $params[] = $tipo;
        }
        if ($desde !== '') {
            $where[] = 'r.fecha_registro >= ?';
            $params[] = $desde . ' 00:00:00';
        }
        if ($hasta !== '') {
            $where[] = 'r.fecha_registro <= ?';
            $params[] = $hasta . ' 23:59:59';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->scalar('SELECT COUNT(*) FROM registro_energia r' . $whereSql, $params);
        $pagination = paginate($total, 12, (int)($_GET['page'] ?? 1));

        $items = $db->fetchAll(
            'SELECT r.*, e.nombre AS estacion, e.codigo
               FROM registro_energia r
               JOIN estaciones e ON e.id_estacion = r.id_estacion_fk'
            . $whereSql .
            ' ORDER BY r.fecha_registro DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $stats = $db->fetch(
            'SELECT COUNT(*) total,
                    COALESCE(SUM(r.cantidad_kwh), 0) kwh,
                    COALESCE(SUM(r.total_costo), 0) costo,
                    COALESCE(AVG(r.costo_kwh), 0) promedio
               FROM registro_energia r' . $whereSql,
            $params
        );

        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime("first day of -{$i} month");
            $months[date('Y-m', $ts)] = 0;
        }
        $rows = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_registro, '%Y-%m') ym, COALESCE(SUM(cantidad_kwh),0) kwh
               FROM registro_energia WHERE fecha_registro >= ? GROUP BY ym",
            [array_key_first($months) . '-01 00:00:00']
        );
        foreach ($rows as $row) {
            if (isset($months[$row['ym']])) {
                $months[$row['ym']] = (float)$row['kwh'];
            }
        }
        $nombres = ['01' => 'ene', '02' => 'feb', '03' => 'mar', '04' => 'abr', '05' => 'may', '06' => 'jun', '07' => 'jul', '08' => 'ago', '09' => 'sep', '10' => 'oct', '11' => 'nov', '12' => 'dic'];
        $chart = ['labels' => [], 'values' => []];
        foreach (array_keys($months) as $key) {
            [$y, $m] = explode('-', $key);
            $chart['labels'][] = $nombres[$m] . ' ' . substr($y, 2);
            $chart['values'][] = $months[$key];
        }

        $stations = $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre');
        $tiposUsados = $db->fetchAll('SELECT DISTINCT tipo_operac FROM registro_energia ORDER BY tipo_operac');

        view('energy/index', [
            'pageTitle' => 'Energía',
            'items' => $items,
            'stats' => $stats,
            'stations' => $stations,
            'tiposUsados' => $tiposUsados,
            'pagination' => $pagination,
            'filters' => ['estacion' => $stationId, 'tipo' => $tipo, 'desde' => $desde, 'hasta' => $hasta],
            'chartData' => ['energia' => $chart],
            'assets' => ['chart' => true, 'charts' => true],
        ]);
    }

    public function create(): void
    {
        view('energy/form', [
            'pageTitle' => 'Nuevo registro de energía',
            'item' => [
                'id_estacion_fk' => '',
                'tipo_operac' => 'Recarga de baterías',
                'cantidad_kwh' => '',
                'costo_kwh' => '650',
                'descripcion' => '',
                'fecha_registro' => date('Y-m-d\TH:i'),
            ],
            'stations' => Database::getInstance()->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre'),
            'tipos' => $this->tipos,
            'errors' => [],
            'action' => url('energia'),
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $data = $this->normalize($_POST);
        $errors = $this->validate($data);

        if (!isset($errors['id_estacion_fk']) && (int)$db->scalar('SELECT COUNT(*) FROM estaciones WHERE id_estacion = ?', [(int)$data['id_estacion_fk']]) === 0) {
            $errors['id_estacion_fk'] = 'La estación seleccionada no existe.';
        }

        if (!empty($errors)) {
            view('energy/form', [
                'pageTitle' => 'Nuevo registro de energía',
                'item' => $data,
                'stations' => $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre'),
                'tipos' => $this->tipos,
                'errors' => $errors,
                'action' => url('energia'),
                'isEdit' => false,
            ]);
            return;
        }

        $db->execute(
            'INSERT INTO registro_energia (id_estacion_fk, tipo_operac, cantidad_kwh, costo_kwh, fecha_registro, descripcion)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                (int)$data['id_estacion_fk'],
                $data['tipo_operac'],
                (float)$data['cantidad_kwh'],
                (float)$data['costo_kwh'],
                $data['fecha_registro'],
                $data['descripcion'] ?: null,
            ]
        );

        flash('success', 'Registro de energía creado correctamente.');
        redirect('energia');
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $item = $db->fetch('SELECT * FROM registro_energia WHERE id_registro = ?', [(int)$id]);
        if (!$item) {
            abort(404);
        }
        $item['fecha_registro'] = date('Y-m-d\TH:i', strtotime($item['fecha_registro']));

        view('energy/form', [
            'pageTitle' => 'Editar registro de energía',
            'item' => $item,
            'stations' => $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre'),
            'tipos' => $this->tipos,
            'errors' => [],
            'action' => url('energia/' . (int)$id),
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $db = Database::getInstance();
        $energyId = (int)$id;
        $item = $db->fetch('SELECT * FROM registro_energia WHERE id_registro = ?', [$energyId]);
        if (!$item) {
            abort(404);
        }

        $data = $this->normalize($_POST);
        $errors = $this->validate($data);
        if (!isset($errors['id_estacion_fk']) && (int)$db->scalar('SELECT COUNT(*) FROM estaciones WHERE id_estacion = ?', [(int)$data['id_estacion_fk']]) === 0) {
            $errors['id_estacion_fk'] = 'La estación seleccionada no existe.';
        }

        if (!empty($errors)) {
            view('energy/form', [
                'pageTitle' => 'Editar registro de energía',
                'item' => array_merge($item, $data),
                'stations' => $db->fetchAll('SELECT id_estacion, nombre FROM estaciones ORDER BY nombre'),
                'tipos' => $this->tipos,
                'errors' => $errors,
                'action' => url('energia/' . $energyId),
                'isEdit' => true,
            ]);
            return;
        }

        $db->execute(
            'UPDATE registro_energia
                SET id_estacion_fk = ?, tipo_operac = ?, cantidad_kwh = ?, costo_kwh = ?, fecha_registro = ?, descripcion = ?
              WHERE id_registro = ?',
            [
                (int)$data['id_estacion_fk'],
                $data['tipo_operac'],
                (float)$data['cantidad_kwh'],
                (float)$data['costo_kwh'],
                $data['fecha_registro'],
                $data['descripcion'] ?: null,
                $energyId,
            ]
        );

        flash('success', 'Registro de energía actualizado.');
        redirect('energia');
    }

    public function destroy(string $id): void
    {
        $db = Database::getInstance();
        $energyId = (int)$id;
        if (!$db->fetch('SELECT id_registro FROM registro_energia WHERE id_registro = ?', [$energyId])) {
            abort(404);
        }

        $db->execute('DELETE FROM registro_energia WHERE id_registro = ?', [$energyId]);
        flash('success', 'Registro de energía eliminado.');
        redirect('energia');
    }

    private function normalize(array $input): array
    {
        $fecha = trim((string)($input['fecha_registro'] ?? ''));
        $ts = $fecha !== '' ? strtotime($fecha) : false;
        return [
            'id_estacion_fk' => (string)($input['id_estacion_fk'] ?? ''),
            'tipo_operac' => trim((string)($input['tipo_operac'] ?? '')),
            'cantidad_kwh' => trim((string)($input['cantidad_kwh'] ?? '')),
            'costo_kwh' => trim((string)($input['costo_kwh'] ?? '')),
            'fecha_registro' => $ts ? date('Y-m-d H:i:s', $ts) : '',
            'descripcion' => trim((string)($input['descripcion'] ?? '')),
        ];
    }

    private function validate(array $data): array
    {
        $errors = Validator::validate($data, [
            'id_estacion_fk' => 'required|int',
            'tipo_operac' => 'required|max:50',
            'cantidad_kwh' => 'required|numeric',
            'costo_kwh' => 'required|numeric',
            'fecha_registro' => 'required|date',
            'descripcion' => 'max:1000',
        ]);

        if (!isset($errors['cantidad_kwh']) && (float)$data['cantidad_kwh'] <= 0) {
            $errors['cantidad_kwh'] = 'La cantidad de kWh debe ser mayor que cero.';
        }
        if (!isset($errors['costo_kwh']) && (float)$data['costo_kwh'] <= 0) {
            $errors['costo_kwh'] = 'El costo por kWh debe ser mayor que cero.';
        }
        return $errors;
    }
}
