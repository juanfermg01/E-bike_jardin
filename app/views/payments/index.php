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
