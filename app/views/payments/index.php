<?php
/** @var array $transactions */
/** @var array $stats */
/** @var array $methods */
/** @var int $metodosActivos */
/** @var array $tipos */
/** @var array $pagination */
/** @var array $filters */
/** @var bool $isStaff */
/** @var array|null $chartData */
$isAdmin = Auth::isAdmin();
$etiquetas = [
    'tarjeta_credito' => 'Tarjeta de crédito',
    'tarjeta_debito' => 'Tarjeta de débito',
    'transferencia' => 'Transferencia',
    'billetera_digital' => 'Billetera digital',
];
$iconos = [
    'tarjeta_credito' => 'bi-credit-card-2-front',
    'tarjeta_debito' => 'bi-credit-card',
    'transferencia' => 'bi-bank',
    'billetera_digital' => 'bi-wallet2',
];
?>
<div class="page-head">
    <div>
        <h1><?= $isStaff ? 'Pagos' : 'Mis pagos' ?></h1>
        <p><?= $isStaff ? 'Supervisa transacciones, reembolsos y métodos registrados.' : 'Administra tus métodos de pago y revisa el historial de cobros.' ?></p>
    </div>
    <?php if (!$isStaff): ?>
        <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#methodModal"><i class="bi bi-plus-lg me-1"></i> Agregar método</button>
    <?php endif; ?>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
            <div class="stat-label"><?= $isStaff ? 'Recaudado' : 'Total pagado' ?></div>
            <div class="stat-value"><?= format_money($stats['monto']) ?></div>
            <div class="stat-hint">Transacciones aprobadas</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-receipt"></i></div>
            <div class="stat-label">Transacciones</div>
            <div class="stat-value"><?= (int)$stats['total'] ?></div>
            <div class="stat-hint"><?= (int)$stats['aprobadas'] ?> aprobadas</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-label">Pendientes</div>
            <div class="stat-value"><?= (int)$stats['pendientes'] ?></div>
            <div class="stat-hint">Por confirmar</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
            <div class="stat-label"><?= $isStaff ? 'Métodos activos' : 'Mis métodos' ?></div>
            <div class="stat-value"><?= (int)$metodosActivos ?></div>
            <div class="stat-hint"><?= $isStaff ? 'En todo el sistema' : 'Disponibles para pagar' ?></div>
        </div>
    </div>
</div>

<?php if (!$isStaff): ?>
    <div class="row g-3 mb-3">
        <?php if (!$methods): ?>
            <div class="col-12">
                <div class="card"><div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-credit-card-2-front"></i>
                        <h3>No tienes métodos de pago</h3>
                        <p class="small mb-3">Agrega uno para asociarlo a tus próximos alquileres.</p>
                        <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#methodModal"><i class="bi bi-plus-lg me-1"></i> Agregar método</button>
                    </div>
                </div></div>
            </div>
        <?php else: foreach ($methods as $m): ?>
            <div class="col-md-6 col-xl-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="bike-icon"><i class="bi <?= $iconos[$m['tipo']] ?? 'bi-credit-card' ?>"></i></span>
                            <?= badge_estado('activa') ?>
                        </div>
                        <h2 class="h6 mb-1"><?= e($m['alias'] ?: ($etiquetas[$m['tipo']] ?? $m['tipo'])) ?></h2>
                        <div class="text-muted small mb-2"><?= e($etiquetas[$m['tipo']] ?? $m['tipo']) ?> · •••• <?= e($m['ultimos_digitos'] ?: '0000') ?></div>
                        <div class="small text-muted mb-3"><i class="bi bi-calendar3 me-1"></i>Agregado el <?= format_date($m['fecha_creacion'], 'd/m/Y') ?></div>
                        <form method="post" action="<?= url('pagos/metodos/' . $m['id_metodo'] . '/eliminar') ?>"
                              data-confirm="Desactivar método" data-text="El método dejará de aparecer al momento de pagar. Las transacciones anteriores se conservan.">
                            <?= csrf_field() ?>
                            <button class="btn btn-sm btn-outline-danger w-100" type="submit"><i class="bi bi-slash-circle me-1"></i> Desactivar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header"><h2>Distribución por método</h2></div>
                <div class="card-body">
                    <?php if (!$chartData || !$chartData['tiposPago']['values']): ?>
                        <div class="empty-state py-4"><i class="bi bi-pie-chart"></i><h3>Sin datos</h3><p class="small mb-0">Aún no hay pagos aprobados.</p></div>
                    <?php else: ?>
                        <div class="chart-box" style="height:230px"><canvas id="chartTiposPago"></canvas></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header"><h2>Reembolsos</h2></div>
                <div class="card-body">
                    <p class="text-muted small mb-2">Desde la tabla de transacciones puedes marcar un pago aprobado como reembolsado. El cambio queda registrado para auditoría.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <div class="stat-mini flex-grow-1"><strong><?= (int)$stats['reembolsos'] ?></strong><span>Reembolsados</span></div>
                        <div class="stat-mini flex-grow-1"><strong><?= format_money($stats['monto']) ?></strong><span>Recaudado</span></div>
                    </div>
                    <div class="note-box small mt-3 mb-0"><i class="bi bi-shield-check me-1"></i> Solo los administradores pueden ejecutar reembolsos.</div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('pagos') ?>" class="filter-bar">
            <?php if ($isStaff): ?>
                <div class="input-group" style="min-width:240px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" placeholder="Cliente, bici o referencia" value="<?= e($filters['q']) ?>">
                </div>
            <?php endif; ?>
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach (['aprobado' => 'Aprobado', 'pendiente' => 'Pendiente', 'rechazado' => 'Rechazado', 'reembolsado' => 'Reembolsado'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['estado'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['q'] || $filters['estado']): ?>
                <a class="btn btn-link text-muted" href="<?= url('pagos') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> pagos</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Referencia</th>
                    <?php if ($isStaff): ?><th>Cliente</th><?php endif; ?>
                    <th>Bicicleta</th>
                    <th>Método</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <?php if ($isAdmin): ?><th class="text-end">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php if (!$transactions): ?>
                <tr><td colspan="<?= $isAdmin ? 8 : ($isStaff ? 7 : 6) ?>"><div class="empty-state"><i class="bi bi-credit-card"></i><h3>Sin pagos registrados</h3><p class="small mb-0">Los cobros aparecen al finalizar cada alquiler.</p></div></td></tr>
            <?php else: foreach ($transactions as $t): ?>
                <tr>
                    <td><?= format_date($t['fecha_proceso'], 'd/m/Y H:i') ?></td>
                    <td class="small"><?= e($t['referencia_ext'] ?: '—') ?></td>
                    <?php if ($isStaff): ?>
                        <td><?= e($t['cliente']) ?></td>
                    <?php endif; ?>
                    <td><?= e($t['codigo_qr']) ?></td>
                    <td>
                        <div class="small"><?= e($etiquetas[$t['tipo'] ?? ''] ?? 'Pago simulado') ?></div>
                        <?php if (!empty($t['ultimos_digitos'])): ?><small class="text-muted">•••• <?= e($t['ultimos_digitos']) ?></small><?php endif; ?>
                    </td>
                    <td><strong><?= format_money($t['monto']) ?></strong></td>
                    <td><?= badge_estado($t['estado_pago']) ?></td>
                    <?php if ($isAdmin): ?>
                        <td>
                            <div class="actions justify-content-end">
                                <?php if ($t['estado_pago'] === 'aprobado'): ?>
                                    <form method="post" action="<?= url('pagos/reembolsar/' . $t['id_transaccion']) ?>"
                                          data-confirm="Reembolsar pago" data-text="Se marcará como reembolsado el pago de <?= format_money($t['monto']) ?>.">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Reembolsar"><i class="bi bi-arrow-counterclockwise"></i></button>
                                    </form>
                                <?php else: ?>
                                    <a class="btn btn-sm btn-soft" href="<?= url('alquileres/ver/' . (int)$t['id_alquiler_fk']) ?>" title="Ver alquiler"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body py-2">
        <?php render_pagination($pagination, 'pagos', $filters); ?>
    </div>
</div>

<?php if (!$isStaff): ?>
<div class="modal fade" id="methodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="<?= url('pagos/metodos') ?>" class="modal-content border-0" style="border-radius:20px">
            <?= csrf_field() ?>
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="bi bi-credit-card-2-front me-1"></i> Agregar método de pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label" for="tipo">Tipo</label>
                    <select class="form-select" id="tipo" name="tipo" required>
                        <?php foreach ($etiquetas as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="alias">Alias <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="text" class="form-control" id="alias" name="alias" maxlength="50" placeholder="Ej: Mi tarjeta principal">
                </div>
                <div class="mb-2">
                    <label class="form-label" for="ultimos_digitos">Últimos 4 dígitos</label>
                    <input type="text" class="form-control" id="ultimos_digitos" name="ultimos_digitos" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="1234" required>
                    <div class="form-text">Por seguridad solo guardamos los últimos 4 dígitos y un token interno. Nunca el número completo.</div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Agregar método</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($isStaff && $chartData): ?>
<script>window.CHART_DATA = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<?php endif; ?>
