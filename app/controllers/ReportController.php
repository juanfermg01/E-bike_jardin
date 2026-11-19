<?php
declare(strict_types=1);

class ReportController
{
    public function index(): void
    {
        $db = Database::getInstance();
        $desde = trim((string)($_GET['desde'] ?? date('Y-m-01')));
        $hasta = trim((string)($_GET['hasta'] ?? date('Y-m-d')));

        if (!strtotime($desde)) {
            $desde = date('Y-m-01');
        }
        if (!strtotime($hasta)) {
            $hasta = date('Y-m-d');
        }
        if (strtotime($hasta) < strtotime($desde)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $from = $desde . ' 00:00:00';
        $to = $hasta . ' 23:59:59';
        $export = (string)($_GET['export'] ?? '');
        if (in_array($export, ['alquileres', 'inventario', 'energia'], true)) {
            $this->export($export, $from, $to);
        }

        $stats = $db->fetch(
            "SELECT COUNT(*) total,
                    COALESCE(SUM(a.estado = 'completado'), 0) completados,
                    COALESCE(SUM(a.estado = 'activo'), 0) activos,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.duracion_minutos ELSE 0 END), 0) minutos,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.costo_total ELSE 0 END), 0) facturado
               FROM alquileres a
              WHERE a.fecha_inicio BETWEEN ? AND ?",
            [$from, $to]
        );

        $ingresos = (float)$db->scalar(
            "SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE estado_pago = 'aprobado' AND fecha_proceso BETWEEN ? AND ?",
            [$from, $to]
        );
        $costoEnergia = (float)$db->scalar(
            'SELECT COALESCE(SUM(total_costo),0) FROM registro_energia WHERE fecha_registro BETWEEN ? AND ?',
            [$from, $to]
        );
        $costoMant = (float)$db->scalar(
            'SELECT COALESCE(SUM(costo),0) FROM mantenimientos WHERE fecha_inicio BETWEEN ? AND ?',
            [$from, $to]
        );
        $clientes = (int)$db->scalar("SELECT COUNT(DISTINCT id_usuario) FROM alquileres WHERE fecha_inicio BETWEEN ? AND ?", [$from, $to]);
        $promedio = (int)$stats['completados'] > 0 ? (int)round((int)$stats['minutos'] / (int)$stats['completados']) : 0;
        $margen = $ingresos - $costoEnergia - $costoMant;

        $months = $this->lastMonths(12);
        $since = $months['keys'][0] . '-01 00:00:00';
        $incomeRows = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_proceso, '%Y-%m') ym, COALESCE(SUM(monto),0) total
               FROM transacciones WHERE estado_pago = 'aprobado' AND fecha_proceso >= ? GROUP BY ym",
            [$since]
        );
        $rentalRows = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') ym, COUNT(*) total
               FROM alquileres WHERE fecha_inicio >= ? GROUP BY ym",
            [$since]
        );
        $incomeMap = array_column($incomeRows, 'total', 'ym');
        $rentalMap = array_column($rentalRows, 'total', 'ym');
        $incomeChart = ['labels' => $months['labels'], 'values' => []];
        $rentalChart = ['labels' => $months['labels'], 'values' => []];
        foreach ($months['keys'] as $key) {
            $incomeChart['values'][] = (float)($incomeMap[$key] ?? 0);
            $rentalChart['values'][] = (int)($rentalMap[$key] ?? 0);
        }

        $estadosLabels = ['disponible' => 'Disponibles', 'en_uso' => 'En uso', 'mantenimiento' => 'Mantenimiento', 'baja' => 'Baja'];
        $estadoRows = $db->fetchAll('SELECT estado, COUNT(*) total FROM bicicletas GROUP BY estado');
        $estados = ['labels' => [], 'values' => []];
        foreach ($estadoRows as $row) {
            $estados['labels'][] = $estadosLabels[$row['estado']] ?? $row['estado'];
            $estados['values'][] = (int)$row['total'];
        }

        $topStations = $db->fetchAll(
            "SELECT e.nombre, COUNT(a.id_alquiler) total,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.costo_total ELSE 0 END), 0) ingresos
               FROM estaciones e
               LEFT JOIN alquileres a ON a.estacion_orig_fk = e.id_estacion AND a.fecha_inicio BETWEEN ? AND ?
              GROUP BY e.id_estacion ORDER BY total DESC, ingresos DESC LIMIT 6",
            [$from, $to]
        );

        $topClients = $db->fetchAll(
            "SELECT u.nombre, u.email, COUNT(a.id_alquiler) viajes,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.costo_total ELSE 0 END), 0) gasto
               FROM usuarios u
               JOIN alquileres a ON a.id_usuario = u.id_usuario AND a.fecha_inicio BETWEEN ? AND ?
              GROUP BY u.id_usuario ORDER BY gasto DESC, viajes DESC LIMIT 6",
            [$from, $to]
        );

        $resumenMensual = $db->fetchAll(
            "SELECT DATE_FORMAT(a.fecha_inicio, '%Y-%m') ym, COUNT(*) total,
                    COALESCE(SUM(a.duracion_minutos),0) minutos,
                    COALESCE(SUM(CASE WHEN a.estado = 'completado' THEN a.costo_total ELSE 0 END),0) facturado
               FROM alquileres a
              WHERE a.fecha_inicio BETWEEN ? AND ?
              GROUP BY ym ORDER BY ym DESC",
            [$from, $to]
        );

        view('reports/index', [
            'pageTitle' => 'Reportes',
            'desde' => $desde,
            'hasta' => $hasta,
            'stats' => $stats,
            'ingresos' => $ingresos,
            'costoEnergia' => $costoEnergia,
            'costoMant' => $costoMant,
            'clientes' => $clientes,
            'promedio' => $promedio,
            'margen' => $margen,
            'topStations' => $topStations,
            'topClients' => $topClients,
            'resumenMensual' => $resumenMensual,
            'chartData' => [
                'ingresos' => $incomeChart,
                'alquileres' => $rentalChart,
                'estados' => $estados,
            ],
            'assets' => ['chart' => true, 'charts' => true],
        ]);
    }

    private function export(string $tipo, string $from, string $to): never
    {
        $db = Database::getInstance();

        if ($tipo === 'alquileres') {
            $rows = $db->fetchAll(
                'SELECT a.id_alquiler, a.uuid_alquiler, u.nombre cliente, u.email,
                        b.codigo_qr, b.modelo, eo.nombre origen, ed.nombre destino,
                        a.fecha_inicio, a.fecha_fin, a.duracion_minutos, a.tarifa_por_minuto, a.costo_total, a.estado
                   FROM alquileres a
                   JOIN usuarios u ON u.id_usuario = a.id_usuario
                   JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
                   JOIN estaciones eo ON eo.id_estacion = a.estacion_orig_fk
                   LEFT JOIN estaciones ed ON ed.id_estacion = a.estacion_dest_fk
                  WHERE a.fecha_inicio BETWEEN ? AND ?
                  ORDER BY a.fecha_inicio DESC',
                [$from, $to]
            );
            $header = ['ID', 'UUID', 'Cliente', 'Correo', 'Bicicleta', 'Modelo', 'Origen', 'Destino', 'Inicio', 'Fin', 'Minutos', 'Tarifa', 'Costo total', 'Estado'];
            $filename = 'alquileres_' . date('Ymd_His') . '.csv';
        } elseif ($tipo === 'inventario') {
            $rows = $db->fetchAll(
                'SELECT b.id_bicicleta, b.codigo_qr, b.modelo, b.num_serie, e.nombre estacion,
                        b.nivel_bateria, b.estado, b.kilometraje, b.ult_manten_date, b.prox_manten_date
                   FROM bicicletas b
                   LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
                  ORDER BY b.codigo_qr'
            );
            $header = ['ID', 'Código QR', 'Modelo', 'Número de serie', 'Estación', 'Batería %', 'Estado', 'Kilometraje', 'Último mantenimiento', 'Próximo mantenimiento'];
            $filename = 'inventario_' . date('Ymd_His') . '.csv';
        } else {
            $rows = $db->fetchAll(
                'SELECT r.id_registro, e.nombre estacion, r.tipo_operac, r.cantidad_kwh, r.costo_kwh,
                        r.total_costo, r.fecha_registro, r.descripcion
                   FROM registro_energia r
                   JOIN estaciones e ON e.id_estacion = r.id_estacion_fk
                  WHERE r.fecha_registro BETWEEN ? AND ?
                  ORDER BY r.fecha_registro DESC',
                [$from, $to]
            );
            $header = ['ID', 'Estación', 'Tipo de operación', 'kWh', 'Costo por kWh', 'Total', 'Fecha', 'Descripción'];
            $filename = 'energia_' . date('Ymd_His') . '.csv';
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, $header, ';');
        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ';');
        }
        fclose($out);
        exit;
    }

    private function lastMonths(int $count): array
    {
        $labels = [];
        $keys = [];
        $names = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        for ($i = $count - 1; $i >= 0; $i--) {
            $ts = strtotime("first day of -{$i} month");
            $keys[] = date('Y-m', $ts);
            $labels[] = $names[(int)date('n', $ts) - 1] . ' ' . date('y', $ts);
        }
        return ['labels' => $labels, 'keys' => $keys];
    }
}
