<?php
/** @var array $items */
/** @var array $stats */
/** @var array $stations */
/** @var array $tiposUsados */
/** @var array $pagination */
/** @var array $filters */
/** @var array $chartData */
$isAdmin = Auth::isAdmin();
?>
<div class="page-head">
    <div>
        <h1>Energía</h1>
        <p>Controla el consumo eléctrico y el costo de operación de cada estación.</p>
    </div>
    <a href="<?= url('energia/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Nuevo registro</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
            <div class="stat-label">kWh registrados</div>
            <div class="stat-value"><?= number_format((float)$stats['kwh'], 1, ',', '.') ?></div>
            <div class="stat-hint"><?= (int)$stats['total'] ?> registros</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Costo acumulado</div>
            <div class="stat-value"><?= format_money($stats['costo']) ?></div>
            <div class="stat-hint">Según los filtros</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-speedometer"></i></div>
            <div class="stat-label">Costo promedio kWh</div>
            <div class="stat-value"><?= format_money($stats['promedio']) ?></div>
            <div class="stat-hint">Tarifa de referencia</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-plug"></i></div>
            <div class="stat-label">Estaciones monitoreadas</div>
            <div class="stat-value"><?= count($stations) ?></div>
            <div class="stat-hint">Puntos de recarga</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h2>Registrar consumo</h2></div>
            <div class="card-body d-flex flex-column justify-content-center">
                <p class="text-muted small">Lleva el control del kWh comprado o consumido por cada estación. El costo total se calcula automáticamente en la base de datos.</p>
                <div class="note-box small mb-0">
                    <i class="bi bi-calculator me-1"></i> Total = cantidad de kWh × costo por kWh. La columna <strong>total_costo</strong> es generada por MySQL.
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><h2>Consumo de los últimos 6 meses</h2></div>
            <div class="card-body"><div class="chart-box" style="height:230px"><canvas id="chartEnergia"></canvas></div></div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('energia') ?>" class="filter-bar">
            <select name="estacion" class="form-select">
                <option value="0">Todas las estaciones</option>
                <?php foreach ($stations as $s): ?>
                    <option value="<?= (int)$s['id_estacion'] ?>" <?= (int)$filters['estacion'] === (int)$s['id_estacion'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="tipo" class="form-select">
                <option value="">Todos los tipos</option>
                <?php foreach ($tiposUsados as $t): ?>
                    <option value="<?= e($t['tipo_operac']) ?>" <?= $filters['tipo'] === $t['tipo_operac'] ? 'selected' : '' ?>><?= e($t['tipo_operac']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="desde" class="form-control" value="<?= e($filters['desde']) ?>" title="Desde">
            <input type="date" name="hasta" class="form-control" value="<?= e($filters['hasta']) ?>" title="Hasta">
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['estacion'] || $filters['tipo'] || $filters['desde'] || $filters['hasta']): ?>
                <a class="btn btn-link text-muted" href="<?= url('energia') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estación</th>
                    <th>Tipo</th>
                    <th>kWh</th>
                    <th>Costo kWh</th>
                    <th>Total</th>
                    <th>Descripción</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-battery-charging"></i><h3>Sin registros de energía</h3><p class="small mb-0">Ajusta los filtros o registra un nuevo consumo.</p></div></td></tr>
            <?php else: foreach ($items as $r): ?>
                <tr>
                    <td><?= format_date($r['fecha_registro'], 'd/m/Y H:i') ?></td>
                    <td>
                        <div class="fw-semibold small"><?= e($r['estacion']) ?></div>
                        <small class="text-muted"><?= e($r['codigo']) ?></small>
                    </td>
                    <td><?= e($r['tipo_operac']) ?></td>
                    <td><?= number_format((float)$r['cantidad_kwh'], 2, ',', '.') ?></td>
                    <td><?= format_money($r['costo_kwh']) ?></td>
                    <td><strong><?= format_money($r['total_costo']) ?></strong></td>
                    <td class="small text-muted"><?= e($r['descripcion'] ?: '—') ?></td>
                    <td>
                        <div class="actions justify-content-end">
                            <a class="btn btn-sm btn-soft" href="<?= url('energia/editar/' . $r['id_registro']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <?php if ($isAdmin): ?>
                                <form method="post" action="<?= url('energia/' . $r['id_registro'] . '/eliminar') ?>"
                                      data-confirm="Eliminar registro" data-text="Se eliminará el registro de energía del <?= format_date($r['fecha_registro'], 'd/m/Y') ?>.">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body py-2">
        <?php render_pagination($pagination, 'energia', $filters); ?>
    </div>
</div>

<script>window.CHART_DATA = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
