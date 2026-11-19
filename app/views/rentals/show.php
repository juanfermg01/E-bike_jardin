<?php
/** @var array $rental */
/** @var array $transactions */
/** @var int $minutes */
/** @var float $estimado */
/** @var bool $isStaff */
$activo = $rental['estado'] === 'activo';
$tipos = [
    'tarjeta_credito' => 'Tarjeta de crédito',
    'tarjeta_debito' => 'Tarjeta de débito',
    'transferencia' => 'Transferencia',
    'billetera_digital' => 'Billetera digital',
];
?>
<div class="receipt-page">
<div class="page-head no-print">
    <div>
        <h1>Alquiler #<?= (int)$rental['id_alquiler'] ?> <?= badge_estado($rental['estado']) ?></h1>
        <p>Recibo y trazabilidad completa del recorrido.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('alquileres') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
        <?php if ($activo): ?>
            <a href="<?= url('alquileres/finalizar/' . $rental['id_alquiler']) ?>" class="btn btn-brand"><i class="bi bi-stop-circle me-1"></i> Finalizar alquiler</a>
        <?php else: ?>
            <button class="btn btn-soft" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir recibo</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($activo): ?>
    <div class="timer-card mb-3 no-print" data-timer-start="<?= e($rental['fecha_inicio']) ?>" data-timer-rate="<?= (float)$rental['tarifa_por_minuto'] ?>">
        <div class="row align-items-center g-3 position-relative" style="z-index:1">
            <div class="col-md-7">
                <div class="timer-label mb-1"><span class="spinner-grow spinner-grow-sm me-1"></span> Alquiler en curso</div>
                <div class="timer-display" data-timer-elapsed>00:00:00</div>
                <div class="timer-cost mt-1" data-timer-cost><?= format_money($estimado) ?></div>
            </div>
            <div class="col-md-5 text-md-end">
                <div class="small" style="color:rgba(233,246,239,.7)">Inicio: <?= format_date($rental['fecha_inicio'], 'd/m/Y H:i') ?></div>
                <a href="<?= url('alquileres/finalizar/' . $rental['id_alquiler']) ?>" class="btn btn-lime mt-2"><i class="bi bi-stop-circle me-1"></i> Finalizar</a>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="receipt">
            <div class="receipt-head d-flex justify-content-between align-items-start">
                <div class="d-flex align-items-center gap-2">
                    <img src="<?= asset('img/logo.svg') ?>" alt="Logo" width="48" height="48">
                    <div>
                        <h2 class="h5 mb-0">E-Bike Jardín</h2>
                        <small class="text-muted">Comprobante de alquiler</small>
                    </div>
                </div>
                <div class="text-end small text-muted">
                    <div>Recibo #<?= (int)$rental['id_alquiler'] ?></div>
                    <div><?= format_date($rental['fecha_inicio'], 'd/m/Y') ?></div>
                </div>
            </div>

            <div class="receipt-row"><span>UUID del alquiler</span><strong class="small"><?= e($rental['uuid_alquiler']) ?></strong></div>
            <div class="receipt-row"><span>Cliente</span><strong><?= e($rental['cliente']) ?></strong></div>
            <div class="receipt-row"><span>Correo</span><strong><?= e($rental['cliente_email']) ?></strong></div>
            <div class="divider"></div>
            <div class="receipt-row"><span>Bicicleta</span><strong><?= e($rental['codigo_qr']) ?> · <?= e($rental['modelo']) ?></strong></div>
            <div class="receipt-row"><span>Serie</span><strong><?= e($rental['num_serie']) ?></strong></div>
            <div class="receipt-row"><span>Estación de origen</span><strong><?= e($rental['estacion_origen']) ?></strong></div>
            <div class="receipt-row"><span>Estación de destino</span><strong><?= e($rental['estacion_destino'] ?? 'Pendiente') ?></strong></div>
            <div class="divider"></div>
            <div class="receipt-row"><span>Inicio</span><strong><?= format_date($rental['fecha_inicio']) ?></strong></div>
            <div class="receipt-row"><span>Fin</span><strong><?= format_date($rental['fecha_fin']) ?></strong></div>
            <div class="receipt-row"><span>Duración</span><strong><?= format_minutes($rental['duracion_minutos'] !== null ? (int)$rental['duracion_minutos'] : null) ?></strong></div>
            <div class="receipt-row"><span>Tarifa por minuto</span><strong><?= format_money($rental['tarifa_por_minuto']) ?></strong></div>
            <div class="receipt-row receipt-total">
                <span>Total <?= $activo ? 'estimado' : 'pagado' ?></span>
                <strong class="<?= $activo ? 'text-warning' : 'text-success' ?>">
                    <?= format_money($rental['costo_total'] !== null ? $rental['costo_total'] : $estimado) ?>
                </strong>
            </div>

            <div class="text-center text-muted small mt-4">
                Gracias por moverte con energía limpia por Jardín, Antioquia.<br>
                E-Bike Jardín · <?= e(APP_NAME) ?> v<?= e(APP_VERSION) ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5 no-print">
        <div class="card">
            <div class="card-header"><h2>Estado del recorrido</h2></div>
            <div class="card-body">
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Estado</span><?= badge_estado($rental['estado']) ?>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Duración</span><strong><?= format_minutes($minutes) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted small">Transacciones</span><strong><?= count($transactions) ?></strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted small">Total</span>
                    <strong class="text-success"><?= format_money($rental['costo_total'] !== null ? $rental['costo_total'] : $estimado) ?></strong>
                </div>

                <?php if ($activo && $isStaff): ?>
                    <form method="post" action="<?= url('alquileres/' . $rental['id_alquiler'] . '/cancelar') ?>" class="mt-3"
                          data-confirm="Cancelar alquiler" data-text="La bicicleta volverá a estar disponible y no se generará cobro.">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i> Cancelar alquiler</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Pagos</h2></div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Referencia</th><th>Método</th><th>Monto</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if (!$transactions): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4 small">Sin pagos registrados todavía.</td></tr>
                    <?php else: foreach ($transactions as $t): ?>
                        <tr>
                            <td class="small"><?= e($t['referencia_ext'] ?: '—') ?><br><small class="text-muted"><?= format_date($t['fecha_proceso'], 'd/m/Y H:i') ?></small></td>
                            <td class="small"><?= e($tipos[$t['tipo'] ?? ''] ?? 'Simulado') ?><?= $t['ultimos_digitos'] ? '<br><small class="text-muted">•••• ' . e($t['ultimos_digitos']) . '</small>' : '' ?></td>
                            <td><?= format_money($t['monto']) ?></td>
                            <td><?= badge_estado($t['estado_pago']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
