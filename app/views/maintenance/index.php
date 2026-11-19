<?php
/** @var array $items */
/** @var array $bikes */
/** @var array $stats */
/** @var array $pagination */
/** @var array $filters */
$isAdmin = Auth::isAdmin();
?>
<div class="page-head">
    <div>
        <h1>Mantenimientos</h1>
        <p>Programa revisiones, registra reparaciones y controla el costo del taller.</p>
    </div>
    <a href="<?= url('mantenimientos/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Nuevo mantenimiento</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div class="stat-label">Mantenimientos</div>
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-hint">Registros históricos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-label">En proceso</div>
            <div class="stat-value"><?= (int)$stats['en_proceso'] ?></div>
            <div class="stat-hint">Bicis en taller</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">Costo total</div>
            <div class="stat-value"><?= format_money($stats['costo_total']) ?></div>
            <div class="stat-hint">Inversión en taller</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-calculator"></i></div>
            <div class="stat-label">Costo promedio</div>
            <div class="stat-value"><?= format_money($stats['costo_promedio']) ?></div>
            <div class="stat-hint">Por mantenimiento</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('mantenimientos') ?>" class="filter-bar">
            <div class="input-group" style="min-width:230px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Bicicleta, tipo o técnico" value="<?= e($filters['q']) ?>">
            </div>
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <option value="proceso" <?= $filters['estado'] === 'proceso' ? 'selected' : '' ?>>En proceso</option>
                <option value="finalizado" <?= $filters['estado'] === 'finalizado' ? 'selected' : '' ?>>Finalizados</option>
            </select>
            <select name="bicicleta" class="form-select">
                <option value="0">Todas las bicicletas</option>
                <?php foreach ($bikes as $b): ?>
                    <option value="<?= (int)$b['id_bicicleta'] ?>" <?= (int)$filters['bicicleta'] === (int)$b['id_bicicleta'] ? 'selected' : '' ?>><?= e($b['codigo_qr']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['q'] || $filters['estado'] || $filters['bicicleta']): ?>
                <a class="btn btn-link text-muted" href="<?= url('mantenimientos') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> registros</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Bicicleta</th>
                    <th>Tipo</th>
                    <th>Técnico</th>
                    <th>Inicio</th>
                    <th>Fin / duración</th>
                    <th>Costo</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$items): ?>
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-tools"></i><h3>Sin mantenimientos</h3><p class="small mb-0">Registra la primera revisión de la flota.</p></div></td></tr>
            <?php else: foreach ($items as $m):
                $enProceso = $m['fecha_fin'] === null;
                $duracion = null;
                if (!$enProceso) {
                    $duracion = max(1, (int)round((strtotime($m['fecha_fin']) - strtotime($m['fecha_inicio'])) / 60));
                }
            ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($m['codigo_qr']) ?></div>
                        <small class="text-muted"><?= e($m['modelo']) ?><?= $m['estacion'] ? ' · ' . e($m['estacion']) : '' ?></small>
                    </td>
                    <td><?= e($m['tipo_manten']) ?></td>
                    <td><?= e($m['tecnico'] ?: '—') ?></td>
                    <td><?= format_date($m['fecha_inicio'], 'd/m/Y H:i') ?></td>
                    <td>
                        <?php if ($enProceso): ?>
                            <span class="text-warning small"><i class="bi bi-hourglass me-1"></i>En proceso</span>
                        <?php else: ?>
                            <div class="small"><?= format_date($m['fecha_fin'], 'd/m/Y H:i') ?></div>
                            <small class="text-muted"><?= format_minutes($duracion) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= format_money($m['costo']) ?></td>
                    <td><?= $enProceso ? badge_estado('mantenimiento') : badge_estado('completado') ?></td>
                    <td>
                        <div class="actions justify-content-end">
                            <a class="btn btn-sm btn-soft" href="<?= url('mantenimientos/editar/' . $m['id_manten']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <a class="btn btn-sm btn-soft" href="<?= url('bicicletas/editar/' . $m['id_bicicleta_fk']) ?>" title="Ver bicicleta"><i class="bi bi-bicycle"></i></a>
                            <?php if ($isAdmin): ?>
                                <form method="post" action="<?= url('mantenimientos/' . $m['id_manten'] . '/eliminar') ?>"
                                      data-confirm="Eliminar mantenimiento" data-text="Se eliminará el registro de mantenimiento de <?= e($m['codigo_qr']) ?>.">
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
        <?php render_pagination($pagination, 'mantenimientos', $filters); ?>
    </div>
</div>
