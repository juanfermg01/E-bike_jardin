<?php
declare(strict_types=1);

class DashboardController
{
    public function index(): void
    {
        Auth::check() ?: redirect('login');
        Auth::isStaff() ? $this->staffDashboard() : $this->clientDashboard();
    }

    private function staffDashboard(): void
    {
        $db = Database::getInstance();

        $stats = [
            'bicis_total' => (int)$db->scalar('SELECT COUNT(*) FROM bicicletas'),
            'disponibles' => (int)$db->scalar("SELECT COUNT(*) FROM bicicletas WHERE estado = 'disponible'"),
            'en_uso' => (int)$db->scalar("SELECT COUNT(*) FROM bicicletas WHERE estado = 'en_uso'"),
            'mantenimiento' => (int)$db->scalar("SELECT COUNT(*) FROM bicicletas WHERE estado = 'mantenimiento'"),
            'estaciones' => (int)$db->scalar('SELECT COUNT(*) FROM estaciones'),
            'clientes' => (int)$db->scalar('SELECT COUNT(*) FROM usuarios WHERE rol_id = 3'),
            'alquileres_activos' => (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE estado = 'activo'"),
            'alquileres_mes' => (int)$db->scalar("SELECT COUNT(*) FROM alquileres WHERE fecha_inicio >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
            'ingresos_mes' => (float)$db->scalar("SELECT COALESCE(SUM(monto),0) FROM transacciones WHERE estado_pago = 'aprobado' AND fecha_proceso >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
            'energia_mes' => (float)$db->scalar("SELECT COALESCE(SUM(total_costo),0) FROM registro_energia WHERE fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')"),
        ];

        $months = $this->lastMonths(6);
        $since = $months['keys'][0] . '-01 00:00:00';

        $rentalsByMonth = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') ym, COUNT(*) total
               FROM alquileres WHERE fecha_inicio >= ? GROUP BY ym",
            [$since]
        );
        $revenueByMonth = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_proceso, '%Y-%m') ym, COALESCE(SUM(monto),0) total
               FROM transacciones WHERE estado_pago = 'aprobado' AND fecha_proceso >= ? GROUP BY ym",
            [$since]
        );

        $rentalsMap = array_column($rentalsByMonth, 'total', 'ym');
        $revenueMap = array_column($revenueByMonth, 'total', 'ym');

        $ingresos = ['labels' => $months['labels'], 'values' => []];
        $alquileresMes = ['labels' => $months['labels'], 'values' => []];
        foreach ($months['keys'] as $key) {
            $ingresos['values'][] = (float)($revenueMap[$key] ?? 0);
            $alquileresMes['values'][] = (int)($rentalsMap[$key] ?? 0);
        }

        $estadosRows = $db->fetchAll('SELECT estado, COUNT(*) total FROM bicicletas GROUP BY estado');
        $estados = ['labels' => [], 'values' => []];
        $labelsEstados = ['disponible' => 'Disponibles', 'en_uso' => 'En uso', 'mantenimiento' => 'Mantenimiento', 'baja' => 'Baja'];
        foreach ($estadosRows as $row) {
            $estados['labels'][] = $labelsEstados[$row['estado']] ?? $row['estado'];
            $estados['values'][] = (int)$row['total'];
        }

        $estaciones = $db->fetchAll('SELECT nombre, bicis_disponibles FROM v_estaciones_mapa ORDER BY nombre');
        $stationChart = [
            'labels' => array_map(fn($r) => mb_strimwidth($r['nombre'], 0, 26, '…'), $estaciones),
            'values' => array_map(fn($r) => (int)$r['bicis_disponibles'], $estaciones),
        ];

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $days[date('Y-m-d', strtotime("-{$i} days"))] = 0;
        }
        $energyRows = $db->fetchAll(
            'SELECT DATE(fecha_registro) d, SUM(cantidad_kwh) kwh
               FROM registro_energia WHERE fecha_registro >= CURDATE() - INTERVAL 6 DAY GROUP BY d'
        );
        foreach ($energyRows as $row) {
            if (isset($days[$row['d']])) {
                $days[$row['d']] = (float)$row['kwh'];
            }
        }
        $energia = [
            'labels' => array_map(fn($d) => date('d/m', strtotime($d)), array_keys($days)),
            'values' => array_values($days),
        ];

        $activeRentals = $db->fetchAll(
            "SELECT a.*, u.nombre usuario, b.codigo_qr, b.modelo, b.nivel_bateria,
                    e.nombre estacion_origen
               FROM alquileres a
               JOIN usuarios u ON u.id_usuario = a.id_usuario
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones e ON e.id_estacion = a.estacion_orig_fk
              WHERE a.estado = 'activo'
              ORDER BY a.fecha_inicio ASC"
        );

        $recentRentals = $db->fetchAll(
            "SELECT a.*, u.nombre usuario, b.codigo_qr
               FROM alquileres a
               JOIN usuarios u ON u.id_usuario = a.id_usuario
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
              ORDER BY a.fecha_inicio DESC LIMIT 6"
        );

        $lowBattery = $db->fetchAll(
            "SELECT b.*, e.nombre estacion
               FROM bicicletas b LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
              WHERE b.estado = 'disponible' AND b.nivel_bateria < 30
              ORDER BY b.nivel_bateria ASC LIMIT 5"
        );

        $upcomingMaintenance = $db->fetchAll(
            "SELECT b.*, e.nombre estacion
               FROM bicicletas b LEFT JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
              WHERE b.estado <> 'baja' AND b.prox_manten_date IS NOT NULL
                AND b.prox_manten_date <= DATE_ADD(CURDATE(), INTERVAL 15 DAY)
              ORDER BY b.prox_manten_date ASC LIMIT 5"
        );

        view('dashboard/admin', [
            'pageTitle' => 'Dashboard',
            'stats' => $stats,
            'activeRentals' => $activeRentals,
            'recentRentals' => $recentRentals,
            'lowBattery' => $lowBattery,
            'upcomingMaintenance' => $upcomingMaintenance,
            'stations' => $estaciones,
            'assets' => ['chart' => true, 'charts' => true],
            'chartData' => [
                'ingresos' => $ingresos,
                'alquileres' => $alquileresMes,
                'estados' => $estados,
                'estaciones' => $stationChart,
                'energia' => $energia,
            ],
        ]);
    }

    private function clientDashboard(): void
    {
        $db = Database::getInstance();
        $userId = Auth::id();

        $activeRental = $db->fetch(
            "SELECT a.*, b.codigo_qr, b.modelo, b.nivel_bateria, b.id_bicicleta,
                    e.nombre estacion_origen
               FROM alquileres a
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones e ON e.id_estacion = a.estacion_orig_fk
              WHERE a.id_usuario = ? AND a.estado = 'activo'
              ORDER BY a.fecha_inicio DESC LIMIT 1",
            [$userId]
        );

        $totals = $db->fetch(
            "SELECT COUNT(*) total, COALESCE(SUM(duracion_minutos),0) minutos, COALESCE(SUM(costo_total),0) gasto
               FROM alquileres WHERE id_usuario = ? AND estado = 'completado'",
            [$userId]
        ) ?? ['total' => 0, 'minutos' => 0, 'gasto' => 0];

        $spent = (float)$db->scalar(
            "SELECT COALESCE(SUM(t.monto),0)
               FROM transacciones t JOIN alquileres a ON a.id_alquiler = t.id_alquiler_fk
              WHERE a.id_usuario = ? AND t.estado_pago = 'aprobado'",
            [$userId]
        );

        $available = (int)$db->scalar("SELECT COUNT(*) FROM bicicletas WHERE estado = 'disponible'");
        $stationsCount = (int)$db->scalar("SELECT COUNT(*) FROM estaciones WHERE estado = 'operativa'");

        $recommended = $db->fetchAll(
            "SELECT b.*, e.nombre estacion
               FROM bicicletas b JOIN estaciones e ON e.id_estacion = b.estacion_act_fk
              WHERE b.estado = 'disponible'
              ORDER BY b.nivel_bateria DESC, b.kilometraje ASC LIMIT 4"
        );

        $recentRentals = $db->fetchAll(
            "SELECT a.*, b.codigo_qr, e.nombre estacion_origen
               FROM alquileres a
               JOIN bicicletas b ON b.id_bicicleta = a.id_bicicleta
               JOIN estaciones e ON e.id_estacion = a.estacion_orig_fk
              WHERE a.id_usuario = ?
              ORDER BY a.fecha_inicio DESC LIMIT 5",
            [$userId]
        );

        $months = $this->lastMonths(6);
        $since = $months['keys'][0] . '-01 00:00:00';
        $rentalsRows = $db->fetchAll(
            "SELECT DATE_FORMAT(fecha_inicio, '%Y-%m') ym, COUNT(*) total
               FROM alquileres WHERE id_usuario = ? AND fecha_inicio >= ? GROUP BY ym",
            [$userId, $since]
        );
        $spentRows = $db->fetchAll(
            "SELECT DATE_FORMAT(t.fecha_proceso, '%Y-%m') ym, COALESCE(SUM(t.monto),0) total
               FROM transacciones t JOIN alquileres a ON a.id_alquiler = t.id_alquiler_fk
              WHERE a.id_usuario = ? AND t.estado_pago = 'aprobado' AND t.fecha_proceso >= ? GROUP BY ym",
            [$userId, $since]
        );
        $rentalsMap = array_column($rentalsRows, 'total', 'ym');
        $spentMap = array_column($spentRows, 'total', 'ym');
        $actividad = ['labels' => $months['labels'], 'values' => []];
        $gastoMes = ['labels' => $months['labels'], 'values' => []];
        foreach ($months['keys'] as $key) {
            $actividad['values'][] = (int)($rentalsMap[$key] ?? 0);
            $gastoMes['values'][] = (float)($spentMap[$key] ?? 0);
        }

        view('dashboard/client', [
            'pageTitle' => 'Mi panel',
            'activeRental' => $activeRental,
            'totals' => $totals,
            'spent' => $spent,
            'available' => $available,
            'stationsCount' => $stationsCount,
            'recommended' => $recommended,
            'recentRentals' => $recentRentals,
            'assets' => ['chart' => true, 'charts' => true, 'leaflet' => true, 'map' => true],
            'chartData' => [
                'alquileres' => $actividad + ['label' => 'Mis viajes'],
                'ingresos' => $gastoMes + ['label' => 'Mi gasto (COP)'],
            ],
        ]);
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
