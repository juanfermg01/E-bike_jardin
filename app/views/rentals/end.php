<?php
/** @var array $rental */
/** @var int $minutes */
/** @var float $estimado */
/** @var array $methods */
/** @var array $stations */
/** @var float $consumo */
/** @var float $km */
$tipos = [
    'tarjeta_credito' => 'Tarjeta de crédito',
    'tarjeta_debito' => 'Tarjeta de débito',
    'transferencia' => 'Transferencia',
    'billetera_digital' => 'Billetera digital',
];
?>
<div class="page-head">
    <div>
        <h1>Finalizar alquiler</h1>
        <p>Confirma la estación de entrega y el método de pago para cerrar el recorrido.</p>
    </div>
    <a href="<?= url('alquileres/ver/' . $rental['id_alquiler']) ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver al detalle</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="timer-card" data-timer-start="<?= e($rental['fecha_inicio']) ?>" data-timer-rate="<?= (float)$rental['tarifa_por_minuto'] ?>">
            <div class="position-relative" style="z-index:1">
                <div class="timer-label mb-1"><span class="spinner-grow spinner-grow-sm me-1"></span> Recorrido en curso</div>
                <div class="timer-display" data-timer-elapsed>00:00:00</div>
                <div class="timer-cost mt-1" data-timer-cost><?= format_money($estimado) ?></div>
                <hr style="border-color:rgba(255,255,255,.15)" class="my-3">
                <div class="d-flex justify-content-between small mb-2"><span style="color:rgba(233,246,239,.7)">Bicicleta</span><strong><?= e($rental['codigo_qr']) ?></strong></div>
                <div class="d-flex justify-content-between small mb-2"><span style="color:rgba(233,246,239,.7)">Estación de origen</span><strong><?= e($rental['estacion_origen']) ?></strong></div>
                <div class="d-flex justify-content-between small mb-2"><span style="color:rgba(233,246,239,.7)">Inicio</span><strong><?= format_date($rental['fecha_inicio'], 'd/m/Y H:i') ?></strong></div>
                <div class="d-flex justify-content-between small mb-2"><span style="color:rgba(233,246,239,.7)">Tarifa</span><strong><?= format_money($rental['tarifa_por_minuto']) ?>/min</strong></div>
                <div class="d-flex justify-content-between small"><span style="color:rgba(233,246,239,.7)">Cliente</span><strong><?= e($rental['cliente']) ?></strong></div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Impacto del recorrido</h2></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-4"><div class="stat-mini text-center"><strong id="estBattery"><?= $consumo ?>%</strong><span>Batería</span></div></div>
                    <div class="col-4"><div class="stat-mini text-center"><strong id="estKm"><?= number_format($km, 1, ',', '.') ?> km</strong><span>Distancia</span></div></div>
                    <div class="col-4"><div class="stat-mini text-center"><strong id="estMinutes"><?= $minutes ?> min</strong><span>Duración</span></div></div>
                </div>
                <div class="note-box small mt-3">
                    <i class="bi bi-info-circle me-1"></i> Al confirmar, la bicicleta quedará disponible en la estación de destino, con la batería descontada y el kilometraje actualizado.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <form method="post" action="<?= url('alquileres/finalizar/' . $rental['id_alquiler']) ?>"
              data-confirm="Finalizar alquiler" data-text="Se calculará el costo final según el tiempo transcurrido y se generará el pago." data-icon="question" data-confirm-text="Sí, finalizar">
            <?= csrf_field() ?>
            <div class="card">
                <div class="card-header"><h2>Entrega y pago</h2></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label" for="estacion_dest_fk">Estación de destino</label>
                        <select class="form-select" id="estacion_dest_fk" name="estacion_dest_fk" required>
                            <?php foreach ($stations as $s): ?>
                                <option value="<?= (int)$s['id_estacion'] ?>" <?= (int)$s['id_estacion'] === (int)$rental['estacion_orig_fk'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Puedes entregar la bicicleta en cualquier estación operativa.</div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="id_metodo_pago">Método de pago</label>
                        <select class="form-select" id="id_metodo_pago" name="id_metodo_pago">
                            <option value="0">Pago simulado (demostración)</option>
                            <?php foreach ($methods as $m): ?>
                                <option value="<?= (int)$m['id_metodo'] ?>">
                                    <?= e($tipos[$m['tipo']] ?? $m['tipo']) ?><?= $m['ultimos_digitos'] ? ' •••• ' . e($m['ultimos_digitos']) : '' ?><?= $m['alias'] ? ' · ' . e($m['alias']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!$methods): ?>
                            <div class="form-text">Este usuario no tiene métodos de pago guardados. Se registrará un pago simulado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h2>Resumen de cobro</h2><span class="chip"><i class="bi bi-clock"></i> cálculo automático</span></div>
                <div class="card-body">
                    <div class="receipt-row"><span>Tiempo transcurrido</span><strong data-timer-minutes><?= $minutes ?> min</strong></div>
                    <div class="receipt-row"><span>Tarifa por minuto</span><strong><?= format_money($rental['tarifa_por_minuto']) ?></strong></div>
                    <div class="receipt-row"><span>Consumo de batería</span><strong data-timer-battery>-<?= $consumo ?>%</strong></div>
                    <div class="receipt-row"><span>Kilometraje estimado</span><strong data-timer-km><?= number_format($km, 1, ',', '.') ?> km</strong></div>
                    <div class="receipt-row receipt-total"><span>Total a pagar</span><strong class="text-success" data-timer-total><?= format_money($estimado) ?></strong></div>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-brand btn-lg" type="submit"><i class="bi bi-check-circle me-1"></i> Confirmar y pagar</button>
                        <a class="btn btn-outline-secondary" href="<?= url('alquileres/ver/' . $rental['id_alquiler']) ?>">Seguir con el recorrido</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const box = document.querySelector('.timer-card');
    if (!box) return;
    const start = new Date(box.dataset.timerStart.replace(' ', 'T')).getTime();
    const rate = parseFloat(box.dataset.timerRate || '0');
    const fmt = function (value) { return '$ ' + Math.round(value).toLocaleString('es-CO') + ' COP'; };
    const set = function (selector, text) {
        const el = document.querySelector(selector);
        if (el) el.textContent = text;
    };

    function update() {
        const diff = Math.max(0, Date.now() - start);
        const minutes = Math.max(1, Math.ceil(diff / 60000));
        const seconds = Math.floor(diff / 1000) % 60;
        const mm = Math.floor(minutes);
        const h = Math.floor(mm / 60);
        const m = mm % 60;
        const elapsed = box.querySelector('[data-timer-elapsed]');
        if (elapsed) {
            elapsed.textContent = String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }
        const cost = minutes * rate;
        set('[data-timer-cost]', fmt(cost));
        set('[data-timer-total]', fmt(cost));
        set('[data-timer-minutes]', mm + ' min');
        const battery = Math.min(45, Math.max(2, minutes * 0.35));
        const km = minutes * 0.22;
        set('[data-timer-battery]', '-' + battery.toFixed(1) + '%');
        set('[data-timer-km]', km.toFixed(1).replace('.', ',') + ' km');
        set('#estBattery', battery.toFixed(1) + '%');
        set('#estKm', km.toFixed(1).replace('.', ',') + ' km');
        set('#estMinutes', minutes + ' min');
    }
    update();
    setInterval(update, 1000);
});
</script>
