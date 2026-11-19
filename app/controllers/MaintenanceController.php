<?php
declare(strict_types=1);

class MaintenanceController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $q = trim((string)($_GET['q'] ?? ''));
        $estado = (string)($_GET['estado'] ?? '');
        $bikeId = (int)($_GET['bicicleta'] ?? 0);

        $where = [];
        $params = [];
        if ($q !== '') {
            $where[] = '(b.codigo_qr LIKE ? OR m.tipo_manten LIKE ? OR m.tecnico LIKE ? OR m.descripcion LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like);
        }
        if ($estado === 'proceso') {
            $where[] = 'm.fecha_fin IS NULL';
        } elseif ($estado === 'finalizado') {
            $where[] = 'm.fecha_fin IS NOT NULL';
        }
        if ($bikeId > 0) {
            $where[] = 'm.id_bicicleta_fk = ?';
            $params[] = $bikeId;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->scalar('SELECT COUNT(*) FROM mantenimientos m JOIN bicicletas b ON b.id_bicicleta = m.id_bicicleta_fk' . $whereSql, $params);
        $pagination = paginate($total, 12, (int)($_GET['page'] ?? 1));

        $items = $db->fetchAll(
            'SELECT m.*, b.codigo_qr, b.modelo, e.nombre AS estacion
               FROM mantenimientos m
               JOIN bicicletas b ON b.id_bicicleta = m.id_bicicleta_fk
               LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk'
            . $whereSql .
            ' ORDER BY m.fecha_inicio DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $stats = $db->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(m.fecha_fin IS NULL), 0) AS en_proceso,
                    COALESCE(SUM(m.fecha_fin IS NOT NULL), 0) AS finalizados,
                    COALESCE(SUM(m.costo), 0) AS costo_total,
                    COALESCE(AVG(NULLIF(m.costo, 0)), 0) AS costo_promedio
               FROM mantenimientos m"
        );

        $bikes = $db->fetchAll('SELECT id_bicicleta, codigo_qr, modelo FROM bicicletas ORDER BY codigo_qr');

        view('maintenance/index', [
            'pageTitle' => 'Mantenimientos',
            'items' => $items,
            'bikes' => $bikes,
            'stats' => $stats,
            'pagination' => $pagination,
            'filters' => ['q' => $q, 'estado' => $estado, 'bicicleta' => $bikeId],
        ]);
    }

    public function create(): void
    {
        $db = Database::getInstance();
        view('maintenance/form', [
            'pageTitle' => 'Nuevo mantenimiento',
            'item' => [
                'id_bicicleta_fk' => (string)($_GET['bici'] ?? ''),
                'tipo_manten' => '',
                'descripcion' => '',
                'tecnico' => Auth::user()['nombre'] ?? '',
                'fecha_inicio' => date('Y-m-d\TH:i'),
                'fecha_fin' => '',
                'costo' => '0',
            ],
            'bikes' => $this->bikesForSelect(),
            'errors' => [],
            'action' => url('mantenimientos'),
            'isEdit' => false,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $data = $this->normalize($_POST);
        $errors = $this->validate($data);

        if (!isset($errors['id_bicicleta_fk'])) {
            $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ?', [(int)$data['id_bicicleta_fk']]);
            if (!$bike) {
                $errors['id_bicicleta_fk'] = 'La bicicleta seleccionada no existe.';
            } elseif ($bike['estado'] === 'baja') {
                $errors['id_bicicleta_fk'] = 'No se puede programar mantenimiento a una bicicleta dada de baja.';
            } elseif ((int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE id_bicicleta = ? AND estado = 'activo'", [(int)$data['id_bicicleta_fk']]) > 0) {
                $errors['id_bicicleta_fk'] = 'La bicicleta tiene un alquiler activo. Finalízalo antes del mantenimiento.';
            }
        }

        if (!empty($errors)) {
            view('maintenance/form', [
                'pageTitle' => 'Nuevo mantenimiento',
                'item' => $data,
                'bikes' => $this->bikesForSelect(),
                'errors' => $errors,
                'action' => url('mantenimientos'),
                'isEdit' => false,
            ]);
            return;
        }

        $db->begin();
        try {
            $db->execute(
                'INSERT INTO mantenimientos (id_bicicleta_fk, tipo_manten, descripcion, tecnico, fecha_inicio, fecha_fin, costo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    (int)$data['id_bicicleta_fk'],
                    $data['tipo_manten'],
                    $data['descripcion'] ?: null,
                    $data['tecnico'] ?: null,
                    $data['fecha_inicio'],
                    $data['fecha_fin'] ?: null,
                    (float)$data['costo'],
                ]
            );
            $this->syncBike((int)$data['id_bicicleta_fk'], $data['fecha_inicio'], $data['fecha_fin']);
            $db->commit();
            flash('success', 'Mantenimiento registrado correctamente.');
            redirect('mantenimientos');
        } catch (Throwable $e) {
            $db->rollback();
            log_exception($e);
            flash('error', 'No se pudo registrar el mantenimiento.');
            redirect('mantenimientos/crear');
        }
    }

    public function edit(string $id): void
    {
        $db = Database::getInstance();
        $item = $db->fetch('SELECT * FROM mantenimientos WHERE id_manten = ?', [(int)$id]);
        if (!$item) {
            abort(404);
        }
        $item['fecha_inicio'] = date('Y-m-d\TH:i', strtotime($item['fecha_inicio']));
        $item['fecha_fin'] = $item['fecha_fin'] ? date('Y-m-d\TH:i', strtotime($item['fecha_fin'])) : '';

        view('maintenance/form', [
            'pageTitle' => 'Editar mantenimiento',
            'item' => $item,
            'bikes' => $this->bikesForSelect(),
            'errors' => [],
            'action' => url('mantenimientos/' . (int)$id),
            'isEdit' => true,
        ]);
    }

    public function update(string $id): void
    {
        $db = Database::getInstance();
        $maintenanceId = (int)$id;
        $item = $db->fetch('SELECT * FROM mantenimientos WHERE id_manten = ?', [$maintenanceId]);
        if (!$item) {
            abort(404);
        }

        $data = $this->normalize($_POST);
        $errors = $this->validate($data);
        if (!isset($errors['id_bicicleta_fk']) && (int)$db->scalar('SELECT COUNT(*) FROM bicicletas WHERE id_bicicleta = ?', [(int)$data['id_bicicleta_fk']]) === 0) {
            $errors['id_bicicleta_fk'] = 'La bicicleta seleccionada no existe.';
        }

        if (!empty($errors)) {
            view('maintenance/form', [
                'pageTitle' => 'Editar mantenimiento',
                'item' => array_merge($item, $data),
                'bikes' => $this->bikesForSelect(),
                'errors' => $errors,
                'action' => url('mantenimientos/' . $maintenanceId),
                'isEdit' => true,
            ]);
            return;
        }

        $db->begin();
        try {
            $db->execute(
                'UPDATE mantenimientos
                    SET id_bicicleta_fk = ?, tipo_manten = ?, descripcion = ?, tecnico = ?, fecha_inicio = ?, fecha_fin = ?, costo = ?
                  WHERE id_manten = ?',
                [
                    (int)$data['id_bicicleta_fk'],
                    $data['tipo_manten'],
                    $data['descripcion'] ?: null,
                    $data['tecnico'] ?: null,
                    $data['fecha_inicio'],
                    $data['fecha_fin'] ?: null,
                    (float)$data['costo'],
                    $maintenanceId,
                ]
            );
            if ((int)$item['id_bicicleta_fk'] !== (int)$data['id_bicicleta_fk']) {
                $this->releaseBike((int)$item['id_bicicleta_fk']);
            }
            $this->syncBike((int)$data['id_bicicleta_fk'], $data['fecha_inicio'], $data['fecha_fin']);
            $db->commit();
            flash('success', 'Mantenimiento actualizado correctamente.');
            redirect('mantenimientos');
        } catch (Throwable $e) {
            $db->rollback();
            log_exception($e);
            flash('error', 'No se pudo actualizar el mantenimiento.');
            redirect('mantenimientos/editar/' . $maintenanceId);
        }
    }

    public function destroy(string $id): void
    {
        $db = Database::getInstance();
        $maintenanceId = (int)$id;
        $item = $db->fetch('SELECT * FROM mantenimientos WHERE id_manten = ?', [$maintenanceId]);
        if (!$item) {
            abort(404);
        }

        $db->begin();
        try {
            $db->execute('DELETE FROM mantenimientos WHERE id_manten = ?', [$maintenanceId]);
            $this->releaseBike((int)$item['id_bicicleta_fk']);
            $db->commit();
            flash('success', 'Mantenimiento eliminado.');
        } catch (Throwable $e) {
            $db->rollback();
            log_exception($e);
            flash('error', 'No se pudo eliminar el mantenimiento.');
        }
        redirect('mantenimientos');
    }

    private function syncBike(int $bikeId, string $start, string $end): void
    {
        $db = Database::getInstance();
        $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ?', [$bikeId]);
        if (!$bike) {
            return;
        }

        if ($end === '') {
            $db->execute("UPDATE bicicletas SET estado = 'mantenimiento' WHERE id_bicicleta = ?", [$bikeId]);
            return;
        }

        $prox = date('Y-m-d', strtotime($end . ' +90 days'));
        $db->execute(
            "UPDATE bicicletas
                SET ult_manten_date = ?, prox_manten_date = ?,
                    estado = CASE WHEN estado = 'mantenimiento' THEN 'disponible' ELSE estado END
              WHERE id_bicicleta = ?",
            [date('Y-m-d', strtotime($end)), $prox, $bikeId]
        );
    }

    private function releaseBike(int $bikeId): void
    {
        $db = Database::getInstance();
        $ongoing = (int)$db->scalar('SELECT COUNT(*) FROM mantenimientos WHERE id_bicicleta_fk = ? AND fecha_fin IS NULL', [$bikeId]);
        if ($ongoing === 0) {
            $db->execute("UPDATE bicicletas SET estado = 'disponible' WHERE id_bicicleta = ? AND estado = 'mantenimiento'", [$bikeId]);
        }
    }

    private function bikesForSelect(): array
    {
        return Database::getInstance()->fetchAll(
            "SELECT b.id_bicicleta, b.codigo_qr, b.modelo, b.estado, e.nombre AS estacion
               FROM bicicletas b
               LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
              WHERE b.estado <> 'baja'
              ORDER BY b.codigo_qr"
        );
    }

    private function normalize(array $input): array
    {
        $start = trim((string)($input['fecha_inicio'] ?? ''));
        $end = trim((string)($input['fecha_fin'] ?? ''));
        return [
            'id_bicicleta_fk' => (string)($input['id_bicicleta_fk'] ?? ''),
            'tipo_manten' => trim((string)($input['tipo_manten'] ?? '')),
            'descripcion' => trim((string)($input['descripcion'] ?? '')),
            'tecnico' => trim((string)($input['tecnico'] ?? '')),
            'fecha_inicio' => $this->toSqlDate($start),
            'fecha_fin' => $end !== '' ? $this->toSqlDate($end) : '',
            'costo' => trim((string)($input['costo'] ?? '0')),
        ];
    }

    private function toSqlDate(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : '';
    }

    private function validate(array $data): array
    {
        $errors = Validator::validate($data, [
            'id_bicicleta_fk' => 'required|int',
            'tipo_manten' => 'required|max:100',
            'tecnico' => 'max:150',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'date',
            'costo' => 'numeric',
        ]);

        if (!isset($errors['costo']) && (float)$data['costo'] < 0) {
            $errors['costo'] = 'El costo no puede ser negativo.';
        }
        if (!isset($errors['fecha_fin']) && $data['fecha_fin'] !== '' && strtotime($data['fecha_fin']) < strtotime($data['fecha_inicio'])) {
            $errors['fecha_fin'] = 'La fecha de fin no puede ser anterior a la de inicio.';
        }
        return $errors;
    }
}
