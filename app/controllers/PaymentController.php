<?php
declare(strict_types=1);

class PaymentController
{
    private array $tipos = ['tarjeta_credito', 'tarjeta_debito', 'transferencia', 'billetera_digital'];
    private array $estados = ['pendiente', 'aprobado', 'rechazado', 'reembolsado'];

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
            $where[] = '(u.nombre LIKE ? OR b.codigo_qr LIKE ? OR t.referencia_ext LIKE ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        if (in_array($estado, $this->estados, true)) {
            $where[] = 't.estado_pago = ?';
            $params[] = $estado;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        $baseFrom = ' FROM transacciones t
                       JOIN alquileres a ON a.id_alquiler = t.id_alquiler_fk
                       JOIN usuarios u ON u.id_usuario = a.id_usuario
                       JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
                       LEFT JOIN metodos_pago m ON m.id_metodo = t.id_metodo_pago_fk';

        $total = (int)$db->scalar('SELECT COUNT(*)' . $baseFrom . $whereSql, $params);
        $pagination = paginate($total, 12, (int)($_GET['page'] ?? 1));

        $transactions = $db->fetchAll(
            'SELECT t.*, a.uuid_alquiler, a.id_usuario, u.nombre AS cliente,
                    b.codigo_qr, m.tipo, m.alias, m.ultimos_digitos' . $baseFrom . $whereSql .
            ' ORDER BY t.fecha_proceso DESC LIMIT ' . (int)$pagination['per_page'] . ' OFFSET ' . (int)$pagination['offset'],
            $params
        );

        $stats = $db->fetch(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(t.estado_pago = 'aprobado'), 0) aprobadas,
                    COALESCE(SUM(CASE WHEN t.estado_pago = 'aprobado' THEN t.monto ELSE 0 END), 0) monto,
                    COALESCE(SUM(t.estado_pago = 'pendiente'), 0) pendientes,
                    COALESCE(SUM(t.estado_pago = 'reembolsado'), 0) reembolsos" . $baseFrom . $whereSql,
            $params
        );

        $methods = [];
        $metodosActivos = 0;
        if (!$isStaff) {
            $methods = $db->fetchAll(
                'SELECT * FROM metodos_pago WHERE id_usuario = ? AND activo = 1 ORDER BY fecha_creacion DESC',
                [Auth::id()]
            );
            $metodosActivos = count($methods);
        } else {
            $metodosActivos = (int)$db->scalar('SELECT COUNT(*) FROM metodos_pago WHERE activo = 1');
        }

        $chartData = null;
        $assets = [];
        if ($isStaff) {
            $rows = $db->fetchAll(
                "SELECT m.tipo, COUNT(*) total
                   FROM transacciones t
                   JOIN metodos_pago m ON m.id_metodo = t.id_metodo_pago_fk
                  WHERE t.estado_pago = 'aprobado'
                  GROUP BY m.tipo ORDER BY total DESC"
            );
            $labels = [
                'tarjeta_credito' => 'Crédito', 'tarjeta_debito' => 'Débito',
                'transferencia' => 'Transferencia', 'billetera_digital' => 'Billetera',
            ];
            $chartData = ['tiposPago' => [
                'labels' => array_map(fn($r) => $labels[$r['tipo']] ?? $r['tipo'], $rows),
                'values' => array_map(fn($r) => (int)$r['total'], $rows),
            ]];
            $assets = ['chart' => true, 'charts' => true];
        }

        view('payments/index', [
            'pageTitle' => $isStaff ? 'Pagos' : 'Mis pagos',
            'transactions' => $transactions,
            'stats' => $stats,
            'methods' => $methods,
            'metodosActivos' => $metodosActivos,
            'tipos' => $this->tipos,
            'pagination' => $pagination,
            'filters' => ['q' => $q, 'estado' => $estado],
            'isStaff' => $isStaff,
            'chartData' => $chartData,
            'assets' => $assets,
        ]);
    }

    public function storeMethod(): void
    {
        $db = Database::getInstance();
        $data = [
            'tipo' => (string)($_POST['tipo'] ?? ''),
            'alias' => trim((string)($_POST['alias'] ?? '')),
            'ultimos_digitos' => preg_replace('/\D/', '', (string)($_POST['ultimos_digitos'] ?? '')),
        ];

        $errors = Validator::validate($data, [
            'tipo' => 'required|in:tarjeta_credito,tarjeta_debito,transferencia,billetera_digital',
            'alias' => 'max:50',
            'ultimos_digitos' => 'required',
        ]);
        if (!isset($errors['ultimos_digitos']) && strlen($data['ultimos_digitos']) !== 4) {
            $errors['ultimos_digitos'] = 'Ingresa exactamente los últimos 4 dígitos.';
        }

        if (!empty($errors)) {
            flash('error', reset($errors));
            redirect('pagos');
        }

        $db->execute(
            'INSERT INTO metodos_pago (id_usuario, tipo, alias, ultimos_digitos, token_hash, activo)
             VALUES (?, ?, ?, ?, ?, 1)',
            [
                Auth::id(),
                $data['tipo'],
                $data['alias'] ?: null,
                $data['ultimos_digitos'],
                hash('sha256', uuid_v4() . microtime(true)),
            ]
        );

        flash('success', 'Método de pago agregado correctamente.');
        redirect('pagos');
    }

    public function destroyMethod(string $id): void
    {
        $db = Database::getInstance();
        $method = $db->fetch('SELECT * FROM metodos_pago WHERE id_metodo = ?', [(int)$id]);
        if (!$method) {
            abort(404);
        }
        if ((int)$method['id_usuario'] !== Auth::id() && !Auth::isAdmin()) {
            abort(403, 'No puedes modificar un método de pago de otro usuario.');
        }

        $db->execute('UPDATE metodos_pago SET activo = 0 WHERE id_metodo = ?', [(int)$id]);
        flash('success', 'Método de pago desactivado.');
        redirect('pagos');
    }

    public function refund(string $id): void
    {
        $db = Database::getInstance();
        $transaction = $db->fetch('SELECT * FROM transacciones WHERE id_transaccion = ?', [(int)$id]);
        if (!$transaction) {
            abort(404);
        }
        if ($transaction['estado_pago'] !== 'aprobado') {
            flash('error', 'Solo se pueden reembolsar transacciones aprobadas.');
            redirect('pagos');
        }

        $db->execute("UPDATE transacciones SET estado_pago = 'reembolsado' WHERE id_transaccion = ?", [(int)$id]);
        flash('success', 'Transacción marcada como reembolsada.');
        redirect('pagos');
    }
}
