<?php
declare(strict_types=1);

class RentalController
{
    private array $estados = ['activo', 'completado', 'cancelado', 'incidencia'];

    public function index(): void
    {
        $db = Database::getInstance();
        $isStaff = Auth::isStaff();
        $q = trim((string)($_GET['q'] ?? ''));
        $estado = (string)($_GET['estado'] ?? '');

        $where = [];
        $params = [];
        if (!$isStaff) {
            $where[] = 'a.id_usuario = ?';
            $params[] = Auth::id();
        }
        if ($q !== '') {
            $where[] = '(u.nombre LIKE ? OR b.codigo_qr LIKE ? OR a.uuid_alquiler LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (in_array($estado, $this->estados, true)) {
            $where[] = 'a.estado = ?';
            $params[] = $estado;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $total = (int)$db->scalar(
            'SELECT COUNT(*) FROM alquileres a
               JOIN usuarios u ON u.id_usuario = a.id_usuario
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta' . $whereSql,
            $params
        );
        $pagination = paginate($total, 10, (int)($_GET['page'] ?? 1));

        $rentals = $db->fetchAll(
            'SELECT a.*, u.nombre AS cliente, b.codigo_qr, b.modelo,
                    eo.nombre AS estacion_origen, ed.nombre AS estacion_destino,
                    (SELECT COUNT(*) FROM transacciones t WHERE t.id_alquiler_fk = a.id_alquiler AND t.estado_pago = \'aprobado\') AS pagos
               FROM alquileres a
               JOIN usuarios u ON u.id_usuario = a.id_usuario
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones eo ON eo.id_estacion = a.estacion_orig_fk
               LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_dest_fk'
            . $whereSql .
            ' ORDER BY a.fecha_inicio DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $statsWhere = $isStaff ? '' : ' WHERE a.id_usuario = ?';
        $statsParams = $isStaff ? [] : [Auth::id()];
        $stats = $db->fetch(
            "SELECT COUNT(*) AS total,
                    COALESCE(SUM(a.estado = 'activo'), 0) AS activos,
                    COALESCE(SUM(a.estado = 'completado'), 0) AS completados,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.costo_total ELSE 0 END), 0) AS ingresos,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.duracion_minutos ELSE 0 END), 0) AS minutos
               FROM alquileres a" . $statsWhere,
            $statsParams
        );

        $activeRental = $db->fetch(
            "SELECT a.*, b.codigo_qr, b.modelo, e.nombre AS estacion
               FROM alquileres a
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones e ON e.id_estacion = a.estacion_orig_fk
              WHERE a.id_usuario = ? AND a.estado = 'activo' LIMIT 1",
            [Auth::id()]
        );

        view('rentals/index', [
            'pageTitle' => $isStaff ? 'Alquileres' : 'Mis alquileres',
            'rentals' => $rentals,
            'pagination' => $pagination,
            'stats' => $stats,
            'filters' => ['q' => $q, 'estado' => $estado],
            'isStaff' => $isStaff,
            'activeRental' => $activeRental,
        ]);
    }

    public function create(): void
    {
        $db = Database::getInstance();
        $isStaff = Auth::isStaff();

        $available = $db->fetchAll(
            "SELECT b.id_bicicleta, b.codigo_qr, b.modelo, b.nivel_bateria, b.kilometraje,
                    e.id_estacion, e.nombre AS estacion, e.direccion
               FROM bicicletas b
               JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
              WHERE b.estado = 'disponible' AND e.estado = 'operativa'
              ORDER BY e.nombre, b.nivel_bateria DESC"
        );

        $clients = [];
        if ($isStaff) {
            $clients = $db->fetchAll(
                "SELECT u.id_usuario, u.nombre, u.email
                   FROM usuarios u
                   JOIN roles r ON r.id_rol = u.rol_id
                  WHERE r.nivel_acceso = 3 AND u.estado = 'activo'
                  ORDER BY u.nombre"
            );
        }

        $activeRental = $db->fetch(
            "SELECT a.id_alquiler, b.codigo_qr
               FROM alquileres a
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
              WHERE a.id_usuario = ? AND a.estado = 'activo' LIMIT 1",
            [Auth::id()]
        );

        view('rentals/create', [
            'pageTitle' => 'Iniciar alquiler',
            'available' => $available,
            'clients' => $clients,
            'isStaff' => $isStaff,
            'selectedBike' => (int)($_GET['bici'] ?? 0),
            'activeRental' => !$isStaff ? $activeRental : null,
            'tarifa' => TARIFA_POR_MINUTO,
        ]);
    }

    public function store(): void
    {
        $db = Database::getInstance();
        $isStaff = Auth::isStaff();
        $bikeId = (int)($_POST['id_bicicleta'] ?? 0);
        $targetUserId = $isStaff ? (int)($_POST['id_usuario'] ?? 0) : (int)Auth::id();

        $error = null;

        if ($bikeId <= 0) {
            $error = 'Selecciona una bicicleta para iniciar el alquiler.';
        } elseif ($targetUserId <= 0) {
            $error = 'Selecciona el cliente que realizará el alquiler.';
        } else {
            $target = $db->fetch(
                'SELECT u.*, r.nivel_acceso FROM usuarios u JOIN roles r ON r.id_rol = u.rol_id WHERE u.id_usuario = ?',
                [$targetUserId]
            );
            if (!$target || $target['estado'] !== 'activo' || (int)$target['nivel_acceso'] !== 3) {
                $error = 'El usuario seleccionado no es un cliente activo.';
            }
        }

        if ($error !== null) {
            flash('error', $error);
            redirect('alquileres/iniciar');
        }

        $db->begin();
        try {
            $bike = $db->fetch('SELECT * FROM bicicletas WHERE id_bicicleta = ? FOR UPDATE', [$bikeId]);
            if (!$bike || $bike['estado'] !== 'disponible') {
                throw new RuntimeException('La bicicleta ya no está disponible. Actualiza la lista e intenta con otra.');
            }
            if (empty($bike['estacion_act_fk'])) {
                throw new RuntimeException('La bicicleta no tiene una estación asignada. Asígnala antes de alquilarla.');
            }
            if ((float)$bike['nivel_bateria'] < 10) {
                throw new RuntimeException('La batería de esta bicicleta es menor al 10%. Envíala a carga antes de alquilarla.');
            }

            $station = $db->fetch('SELECT * FROM estaciones WHERE id_estacion = ?', [(int)$bike['estacion_act_fk']]);
            if (!$station || $station['estado'] !== 'operativa') {
                throw new RuntimeException('La estación de origen no está operativa.');
            }

            $active = $db->fetch(
                "SELECT id_alquiler FROM alquileres WHERE id_usuario = ? AND estado = 'activo' LIMIT 1 FOR UPDATE",
                [$targetUserId]
            );
            if ($active) {
                throw new RuntimeException('Este usuario ya tiene un alquiler activo. Debe finalizarlo antes de iniciar otro.');
            }

            $now = date('Y-m-d H:i:s');
            $db->execute(
                'INSERT INTO alquileres (id_usuario, id_bicicleta, estacion_orig_fk, uuid_alquiler, fecha_inicio, tarifa_por_minuto, estado)
                 VALUES (?, ?, ?, ?, ?, ?, \'activo\')',
                [$targetUserId, $bikeId, (int)$bike['estacion_act_fk'], uuid_v4(), $now, TARIFA_POR_MINUTO]
            );
            $rentalId = $db->lastInsertId();

            $db->execute("UPDATE bicicletas SET estado = 'en_uso' WHERE id_bicicleta = ?", [$bikeId]);
            $db->commit();

            flash('success', 'Alquiler iniciado. Descuenta tu recorrido al finalizar.');
            redirect('alquileres/ver/' . $rentalId);
        } catch (Throwable $e) {
            $db->rollback();
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo iniciar el alquiler. Intenta de nuevo.');
            if (!($e instanceof RuntimeException)) {
                log_exception($e);
            }
            redirect('alquileres/iniciar');
        }
    }

    public function endForm(string $id): void
    {
        $db = Database::getInstance();
        $rental = $this->findRental((int)$id);
        if (!$rental) {
            abort(404);
        }
        if (!Auth::ownsRental((int)$rental['id_usuario'])) {
            abort(403, 'Este alquiler pertenece a otro usuario.');
        }
        if ($rental['estado'] !== 'activo') {
            flash('info', 'Este alquiler ya fue finalizado o cancelado.');
            redirect('alquileres/ver/' . (int)$id);
        }

        $minutes = max(1, (int)ceil((time() - strtotime($rental['fecha_inicio'])) / 60));
        $methods = $db->fetchAll(
            'SELECT * FROM metodos_pago WHERE id_usuario = ? AND activo = 1 ORDER BY fecha_creacion DESC',
            [(int)$rental['id_usuario']]
        );
        $stations = $db->fetchAll("SELECT id_estacion, nombre FROM estaciones WHERE estado = 'operativa' ORDER BY nombre");

        view('rentals/end', [
            'pageTitle' => 'Finalizar alquiler',
            'rental' => $rental,
            'minutes' => $minutes,
            'estimado' => $minutes * (float)$rental['tarifa_por_minuto'],
            'methods' => $methods,
            'stations' => $stations,
            'consumo' => $this->batteryUse($minutes),
            'km' => $this->distance($minutes),
        ]);
    }

    public function end(string $id): void
    {
        $db = Database::getInstance();
        $rentalId = (int)$id;
        $rental = $this->findRental($rentalId);

        if (!$rental) {
            abort(404);
        }
        if (!Auth::ownsRental((int)$rental['id_usuario'])) {
            abort(403, 'Este alquiler pertenece a otro usuario.');
        }
        if ($rental['estado'] !== 'activo') {
            flash('info', 'Este alquiler ya fue finalizado o cancelado.');
            redirect('alquileres/ver/' . $rentalId);
        }

        $destinationId = (int)($_POST['estacion_dest_fk'] ?? 0);
        $methodId = (int)($_POST['id_metodo_pago'] ?? 0);

        $destination = $db->fetch('SELECT * FROM estaciones WHERE id_estacion = ?', [$destinationId]);
        if (!$destination || $destination['estado'] !== 'operativa') {
            flash('error', 'Selecciona una estación de destino operativa.');
            redirect('alquileres/finalizar/' . $rentalId);
        }

        if ($methodId > 0) {
            $method = $db->fetch(
                'SELECT id_metodo FROM metodos_pago WHERE id_metodo = ? AND id_usuario = ? AND activo = 1',
                [$methodId, (int)$rental['id_usuario']]
            );
            if (!$method) {
                flash('error', 'El método de pago seleccionado no es válido.');
                redirect('alquileres/finalizar/' . $rentalId);
            }
        }

        $db->begin();
        try {
            $locked = $db->fetch('SELECT * FROM alquileres WHERE id_alquiler = ? FOR UPDATE', [$rentalId]);
            if (!$locked || $locked['estado'] !== 'activo') {
                throw new RuntimeException('El alquiler ya no está activo.');
            }

            $endTs = time();
            $startTs = strtotime($locked['fecha_inicio']);
            $minutes = max(1, (int)ceil(($endTs - $startTs) / 60));
            $cost = round($minutes * (float)$locked['tarifa_por_minuto'], 2);
            $batteryUse = $this->batteryUse($minutes);
            $km = $this->distance($minutes);

            $db->execute(
                "UPDATE alquileres
                    SET fecha_fin = ?, estacion_dest_fk = ?, duracion_minutos = ?, costo_total = ?, estado = 'completado'
                  WHERE id_alquiler = ?",
                [date('Y-m-d H:i:s', $endTs), $destinationId, $minutes, $cost, $rentalId]
            );

            $db->execute(
                'UPDATE bicicletas
                    SET estado = \'disponible\',
                        estacion_act_fk = ?,
                        nivel_bateria = GREATEST(0, nivel_bateria - ?),
                        kilometraje = kilometraje + ?
                  WHERE id_bicicleta = ?',
                [$destinationId, $batteryUse, $km, (int)$locked['id_bicicleta']]
            );

            $db->execute(
                'INSERT INTO transacciones (id_alquiler_fk, id_metodo_pago_fk, monto, estado_pago, referencia_ext)
                 VALUES (?, ?, ?, \'aprobado\', ?)',
                [$rentalId, $methodId > 0 ? $methodId : null, $cost, 'EBJ-' . strtoupper(bin2hex(random_bytes(4)))]
            );

            $db->commit();
            flash('success', 'Alquiler finalizado. Total a pagar: ' . format_money($cost));
            redirect('alquileres/ver/' . $rentalId);
        } catch (Throwable $e) {
            $db->rollback();
            log_exception($e);
            flash('error', 'No se pudo finalizar el alquiler. Intenta nuevamente.');
            redirect('alquileres/finalizar/' . $rentalId);
        }
    }

    public function show(string $id): void
    {
        $db = Database::getInstance();
        $rental = $this->findRental((int)$id);
        if (!$rental) {
            abort(404);
        }
        if (!Auth::ownsRental((int)$rental['id_usuario'])) {
            abort(403, 'Este alquiler pertenece a otro usuario.');
        }

        $transactions = $db->fetchAll(
            'SELECT t.*, m.tipo, m.alias, m.ultimos_digitos
               FROM transacciones t
               LEFT JOIN metodos_pago m ON m.id_metodo = t.id_metodo_pago_fk
              WHERE t.id_alquiler_fk = ?
              ORDER BY t.fecha_proceso DESC',
            [(int)$id]
        );

        $minutes = $rental['duracion_minutos'] !== null
            ? (int)$rental['duracion_minutos']
            : max(1, (int)ceil((time() - strtotime($rental['fecha_inicio'])) / 60));

        view('rentals/show', [
            'pageTitle' => 'Alquiler #' . (int)$id,
            'rental' => $rental,
            'transactions' => $transactions,
            'minutes' => $minutes,
            'estimado' => $minutes * (float)$rental['tarifa_por_minuto'],
            'isStaff' => Auth::isStaff(),
        ]);
    }

    public function cancel(string $id): void
    {
        $db = Database::getInstance();
        $rentalId = (int)$id;
        $rental = $this->findRental($rentalId);
        if (!$rental) {
            abort(404);
        }
        if ($rental['estado'] !== 'activo') {
            flash('info', 'Solo se pueden cancelar alquileres activos.');
            redirect('alquileres');
        }

        $db->begin();
        try {
            $locked = $db->fetch('SELECT * FROM alquileres WHERE id_alquiler = ? FOR UPDATE', [$rentalId]);
            if (!$locked || $locked['estado'] !== 'activo') {
                throw new RuntimeException('El alquiler ya no está activo.');
            }

            $minutes = max(1, (int)ceil((time() - strtotime($locked['fecha_inicio'])) / 60));
            $db->execute(
                "UPDATE alquileres
                    SET estado = 'cancelado', fecha_fin = ?, duracion_minutos = ?, costo_total = 0
                  WHERE id_alquiler = ?",
                [date('Y-m-d H:i:s'), $minutes, $rentalId]
            );
            $db->execute("UPDATE bicicletas SET estado = 'disponible' WHERE id_bicicleta = ?", [(int)$locked['id_bicicleta']]);
            $db->commit();

            flash('success', 'Alquiler cancelado. La bicicleta vuelve a estar disponible.');
        } catch (Throwable $e) {
            $db->rollback();
            log_exception($e);
            flash('error', 'No se pudo cancelar el alquiler.');
        }
        redirect('alquileres');
    }

    private function findRental(int $id): ?array
    {
        return Database::getInstance()->fetch(
            'SELECT a.*, u.nombre AS cliente, u.email AS cliente_email,
                    b.codigo_qr, b.modelo, b.num_serie, b.nivel_bateria,
                    eo.nombre AS estacion_origen, ed.nombre AS estacion_destino
               FROM alquileres a
               JOIN usuarios u ON u.id_usuario = a.id_usuario
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones eo ON eo.id_estacion = a.estacion_orig_fk
               LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_dest_fk
              WHERE a.id_alquiler = ?',
            [$id]
        ) ?: null;
    }

    private function batteryUse(int $minutes): float
    {
        return round(min(45, max(2, $minutes * 0.35)), 1);
    }

    private function distance(int $minutes): float
    {
        return round($minutes * 0.22, 2);
    }
}
