<?php
/** @var array $stats */
/** @var float $ingresos */
/** @var float $costoEnergia */
/** @var float $costoMant */
/** @var int $clientes */
/** @var int $promedio */
/** @var float $margen */
/** @var array $topStations */
/** @var array $topClients */
/** @var array $resumenMensual */
/** @var array $chartData */
/** @var string $desde */
/** @var string $hasta */
$query = '&desde=' . urlencode($desde) . '&hasta=' . urlencode($hasta);
?>
<div class="page-head">
    <div>
        <h1>Reportes</h1>
        <p>Analiza ingresos, uso de la flota y costos operativos del periodo seleccionado.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir</button>
        <div class="dropdown">
            <button class="btn btn-brand dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download me-1"></i> Exportar CSV
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><a class="dropdown-item" href="<?= url('reportes?export=alquileres' . $query) ?>"><i class="bi bi-receipt me-2"></i> Alquileres</a></li>
                <li><a class="dropdown-item" href="<?= url('reportes?export=inventario') ?>"><i class="bi bi-bicycle me-2"></i> Inventario de bicicletas</a></li>
                <li><a class="dropdown-item" href="<?= url('reportes?export=energia' . $query) ?>"><i class="bi bi-lightning-charge me-2"></i> Consumo de energía</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="get" action="<?= url('reportes') ?>" class="filter-bar">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 small text-muted" for="desde">Desde</label>
                <input type="date" class="form-control" id="desde" name="desde" value="<?= e($desde) ?>">
            </div>
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 small text-muted" for="hasta">Hasta</label>
                <input type="date" class="form-control" id="hasta" name="hasta" value="<?= e($hasta) ?>">
            </div>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Aplicar</button>
            <a class="btn btn-link text-muted" href="<?= url('reportes') ?>">Mes actual</a>
        </form>
        <span class="text-muted small">Periodo: <?= format_date($desde, 'd/m/Y') ?> — <?= format_date($hasta, 'd/m/Y') ?></span>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label">Ingresos aprobados</div>
            <div class="stat-value"><?= format_money($ingresos) ?></div>
            <div class="stat-hint">Pagos confirmados</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
            <div class="stat-label">Alquileres</div>
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-hint"><?= (int)$stats['completados'] ?> completados · <?= (int)$stats['activos'] ?> activos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">Duración promedio</div>
            <div class="stat-value"><?= format_minutes($promedio) ?></div>
            <div class="stat-hint">Por alquiler completado</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-label">Clientes activos</div>
            <div class="stat-value"><?= (int)$clientes ?></div>
            <div class="stat-hint">Con al menos un viaje</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-4">
        <div class="stat-card danger h-100">
            <div class="stat-icon"><i class="bi bi-battery-charging"></i></div>
            <div class="stat-label">Costo de energía</div>
            <div class="stat-value"><?= format_money($costoEnergia) ?></div>
            <div class="stat-hint">Registros del periodo</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="stat-card warn h-100">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-label">Costo de mantenimiento</div>
            <div class="stat-value"><?= format_money($costoMant) ?></div>
            <div class="stat-hint">Taller y repuestos</div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="stat-card <?= $margen >= 0 ? 'lime' : 'danger' ?> h-100">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-label">Margen operativo</div>
            <div class="stat-value"><?= format_money($margen) ?></div>
            <div class="stat-hint">Ingresos − energía − mantenimiento</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header"><h2>Ingresos de los últimos 12 meses</h2></div>
            <div class="card-body"><div class="chart-box" style="height:260px"><canvas id="chartIngresos"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h2>Estado de la flota</h2></div>
            <div class="card-body"><div class="chart-box" style="height:260px"><canvas id="chartEstados"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header"><h2>Alquileres por mes</h2></div>
            <div class="card-body"><div class="chart-box" style="height:240px"><canvas id="chartAlquileres"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h2>Estaciones con más salidas</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Estación</th><th>Alquileres</th><th>Ingresos</th></tr></thead>
                    <tbody>
                    <?php if (!$topStations): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4 small">Sin datos en el periodo.</td></tr>
                    <?php else: foreach ($topStations as $s): ?>
                        <tr>
                            <td class="small"><?= e($s['nombre']) ?></td>
                            <td><?= (int)$s['total'] ?></td>
                            <td><?= format_money($s['ingresos']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h2>Mejores clientes</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Cliente</th><th>Viajes</th><th>Gasto</th></tr></thead>
                    <tbody>
                    <?php if (!$topClients): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4 small">Sin datos en el periodo.</td></tr>
                    <?php else: foreach ($topClients as $c): ?>
                        <tr>
                            <td>
                                <div class="small fw-semibold"><?= e($c['nombre']) ?></div>
                                <small class="text-muted"><?= e($c['email']) ?></small>
                            </td>
                            <td><?= (int)$c['viajes'] ?></td>
                            <td><?= format_money($c['gasto']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header"><h2>Resumen mensual</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Mes</th><th>Alquileres</th><th>Tiempo total</th><th>Facturado</th></tr></thead>
                    <tbody>
                    <?php if (!$resumenMensual): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4 small">Sin alquileres en el periodo.</td></tr>
                    <?php else: foreach ($resumenMensual as $m): ?>
                        <tr>
                            <td><?= e(date('m/Y', strtotime($m['ym'] . '-01'))) ?></td>
                            <td><?= (int)$m['total'] ?></td>
                            <td><?= format_minutes((int)$m['minutos']) ?></td>
                            <td><strong><?= format_money($m['facturado']) ?></strong></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>window.CHART_DATA = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
