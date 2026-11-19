<?php
/** @var array $rentals */
/** @var array $pagination */
/** @var array $stats */
/** @var array $filters */
/** @var bool $isStaff */
/** @var array|null $activeRental */
$userId = Auth::id();
?>
<div class="page-head">
    <div>
        <h1><?= $isStaff ? 'Alquileres' : 'Mis alquileres' ?></h1>
        <p><?= $isStaff ? 'Controla los recorridos activos, el historial y los ingresos por alquiler.' : 'Consulta tus recorridos, costos y el estado de tu alquiler actual.' ?></p>
    </div>
    <?php if ($isStaff || !$activeRental): ?>
        <a href="<?= url('alquileres/iniciar') ?>" class="btn btn-brand"><i class="bi bi-lightning-charge me-1"></i> Nuevo alquiler</a>
    <?php endif; ?>
</div>

<?php if ($activeRental): ?>
    <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-3">
        <span><i class="bi bi-bicycle me-1"></i> Tienes un alquiler activo con la bicicleta <strong><?= e($activeRental['codigo_qr']) ?></strong> en <?= e($activeRental['estacion']) ?>.</span>
        <a class="btn btn-sm btn-brand" href="<?= url('alquileres/finalizar/' . $activeRental['id_alquiler']) ?>">Finalizar ahora</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <div class="stat-label">Alquileres</div>
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-hint">Registros históricos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-play-circle"></i></div>
            <div class="stat-label">Activos</div>
            <div class="stat-value"><?= (int)$stats['activos'] ?></div>
            <div class="stat-hint">En curso ahora</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-check2-all"></i></div>
            <div class="stat-label">Completados</div>
            <div class="stat-value"><?= (int)$stats['completados'] ?></div>
            <div class="stat-hint"><?= format_minutes((int)$stats['minutos']) ?> recorridos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label"><?= $isStaff ? 'Ingresos' : 'Total gastado' ?></div>
            <div class="stat-value"><?= format_money($stats['ingresos']) ?></div>
            <div class="stat-hint">Alquileres completados</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('alquileres') ?>" class="filter-bar">
            <?php if ($isStaff): ?>
                <div class="input-group" style="min-width:260px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cliente, bicicleta o UUID" value="<?= e($filters['q']) ?>">
                </div>
            <?php endif; ?>
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach (['activo' => 'Activo', 'completado' => 'Completado', 'cancelado' => 'Cancelado', 'incidencia' => 'Incidencia'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['estado'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['q'] || $filters['estado']): ?>
                <a class="btn btn-link text-muted" href="<?= url('alquileres') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <?php if ($isStaff): ?><th>Cliente</th><?php endif; ?>
                    <th>Bicicleta</th>
                    <th>Recorrido</th>
                    <th>Inicio</th>
                    <th>Duración</th>
                    <th>Costo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rentals): ?>
                <tr><td colspan="<?= $isStaff ? 9 : 8 ?>"><div class="empty-state"><i class="bi bi-lightning-charge"></i><h3>Sin alquileres para mostrar</h3><p class="small mb-0">Inicia un recorrido para verlo aquí.</p></div></td></tr>
            <?php else: foreach ($rentals as $r): ?>
                <tr>
                    <td class="text-muted">#<?= (int)$r['id_alquiler'] ?></td>
                    <?php if ($isStaff): ?>
                        <td>
                            <div class="fw-semibold small"><?= e($r['cliente']) ?></div>
                        </td>
                    <?php endif; ?>
                    <td>
                        <div class="fw-semibold"><?= e($r['codigo_qr']) ?></div>
                        <small class="text-muted"><?= e($r['modelo']) ?></small>
                    </td>
                    <td>
                        <div class="small"><?= e($r['estacion_origen']) ?></div>
                        <small class="text-muted"><i class="bi bi-arrow-down me-1"></i><?= e($r['estacion_destino'] ?? 'En curso') ?></small>
                    </td>
                    <td><?= format_date($r['fecha_inicio'], 'd/m/Y H:i') ?></td>
                    <td><?= format_minutes($r['duracion_minutos'] !== null ? (int)$r['duracion_minutos'] : null) ?></td>
                    <td><?= $r['costo_total'] !== null ? format_money($r['costo_total']) : '—' ?></td>
                    <td><?= badge_estado($r['estado']) ?></td>
                    <td>
                        <div class="actions justify-content-end">
                            <?php if ($r['estado'] === 'activo' && ((int)$r['id_usuario'] === $userId || $isStaff)): ?>
                                <a class="btn btn-sm btn-brand" href="<?= url('alquileres/finalizar/' . $r['id_alquiler']) ?>" title="Finalizar"><i class="bi bi-stop-circle"></i></a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-soft" href="<?= url('alquileres/ver/' . $r['id_alquiler']) ?>" title="Ver detalle"><i class="bi bi-eye"></i></a>
                            <?php if ($r['estado'] === 'activo' && $isStaff): ?>
                                <form method="post" action="<?= url('alquileres/' . $r['id_alquiler'] . '/cancelar') ?>"
                                      data-confirm="Cancelar alquiler" data-text="El alquiler #<?= (int)$r['id_alquiler'] ?> se cancelará y la bicicleta volverá a estar disponible.">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Cancelar"><i class="bi bi-x-circle"></i></button>
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
        <?php render_pagination($pagination, 'alquileres', $filters); ?>
    </div>
</div>
