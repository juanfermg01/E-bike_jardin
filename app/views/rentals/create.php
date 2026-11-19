<?php
/** @var array $available */
/** @var array $clients */
/** @var bool $isStaff */
/** @var int $selectedBike */
/** @var array|null $activeRental */
/** @var float $tarifa */
$stations = [];
foreach ($available as $b) {
    $stations[$b['id_estacion']] = $b['estacion'];
}
?>
<div class="page-head">
    <div>
        <h1>Iniciar alquiler</h1>
        <p>Elige una bicicleta disponible y comienza el recorrido. La tarifa se cobra por minuto.</p>
    </div>
    <a href="<?= url('alquileres') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if ($activeRental): ?>
    <div class="alert alert-warning rounded-3 d-flex flex-wrap gap-2 align-items-center justify-content-between">
        <span><i class="bi bi-exclamation-circle me-1"></i> Ya tienes un alquiler activo con la bicicleta <strong><?= e($activeRental['codigo_qr']) ?></strong>. Debes finalizarlo antes de iniciar otro.</span>
        <a class="btn btn-sm btn-brand" href="<?= url('alquileres/finalizar/' . $activeRental['id_alquiler']) ?>">Finalizar alquiler</a>
    </div>
<?php endif; ?>

<?php if (!$available): ?>
    <div class="card"><div class="card-body">
        <div class="empty-state">
            <i class="bi bi-bicycle"></i>
            <h3>No hay bicicletas disponibles</h3>
            <p class="small mb-3">Todas las bicicletas están en uso o en mantenimiento. Intenta más tarde.</p>
            <a href="<?= url('mapa') ?>" class="btn btn-soft"><i class="bi bi-geo-alt me-1"></i> Ver estaciones en el mapa</a>
        </div>
    </div></div>
<?php else: ?>
<form method="post" action="<?= url('alquileres/iniciar') ?>" class="row g-3" <?= $activeRental ? 'style="display:none"' : '' ?>>
    <?= csrf_field() ?>
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h2>Bicicletas disponibles</h2>
                <span class="chip"><i class="bi bi-cash-coin"></i> <?= format_money($tarifa) ?> / min</span>
            </div>
            <div class="card-body">
                <div class="filter-bar mb-3">
                    <div class="input-group" style="min-width:220px">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" class="form-control" id="bikeSearch" placeholder="Buscar código o modelo">
                    </div>
                    <select class="form-select" id="stationFilter" style="max-width:260px">
                        <option value="0">Todas las estaciones</option>
                        <?php foreach ($stations as $id => $nombre): ?>
                            <option value="<?= (int)$id ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-muted small" id="bikeCount"><?= count($available) ?> disponibles</span>
                </div>

                <div class="row g-2" id="bikeGrid">
                    <?php foreach ($available as $b):
                        $bat = (float)$b['nivel_bateria'];
                        $barClass = $bat < 20 ? 'low' : ($bat < 50 ? 'medium' : '');
                        $autonomia = round($bat * 0.6, 1);
                    ?>
                        <div class="col-md-6 bike-option-wrap" data-station="<?= (int)$b['id_estacion'] ?>"
                             data-search="<?= e(mb_strtolower($b['codigo_qr'] . ' ' . $b['modelo'] . ' ' . $b['estacion'])) ?>">
                            <label class="bike-option h-100">
                                <input type="radio" name="id_bicicleta" value="<?= (int)$b['id_bicicleta'] ?>"
                                       <?= $selectedBike === (int)$b['id_bicicleta'] ? 'checked' : '' ?>>
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="bike-icon" style="width:40px;height:40px;font-size:1.2rem"><i class="bi bi-bicycle"></i></span>
                                        <div>
                                            <div class="fw-semibold"><?= e($b['codigo_qr']) ?></div>
                                            <small class="text-muted"><?= e($b['modelo']) ?></small>
                                        </div>
                                    </div>
                                    <span class="pick-check"><i class="bi bi-check-lg"></i></span>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Batería</span><strong><?= $bat ?>%</strong></div>
                                    <div class="battery-bar <?= $barClass ?>"><span style="width: <?= max(3, $bat) ?>%"></span></div>
                                </div>
                                <div class="d-flex justify-content-between small mt-2 text-muted">
                                    <span><i class="bi bi-geo-alt me-1"></i><?= e($b['estacion']) ?></span>
                                    <span><i class="bi bi-signpost me-1"></i>~<?= $autonomia ?> km</span>
                                </div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="empty-state d-none" id="bikeEmpty">
                    <i class="bi bi-search"></i>
                    <h3>Sin resultados</h3>
                    <p class="small mb-0">Cambia la estación o el texto de búsqueda.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card">
            <div class="card-header"><h2>Resumen</h2></div>
            <div class="card-body">
                <?php if ($isStaff): ?>
                    <div class="mb-3">
                        <label class="form-label" for="id_usuario">Cliente</label>
                        <select class="form-select" id="id_usuario" name="id_usuario" required>
                            <option value="">Selecciona un cliente</option>
                            <?php foreach ($clients as $c): ?>
                                <option value="<?= (int)$c['id_usuario'] ?>"><?= e($c['nombre']) ?> · <?= e($c['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">El alquiler quedará a nombre del cliente seleccionado.</div>
                    </div>
                <?php else: ?>
                    <div class="note-box small mb-3">
                        <i class="bi bi-person-check me-1"></i> El alquiler se registrará a tu nombre, <strong><?= e(Auth::user()['nombre']) ?></strong>.
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Bicicleta</span>
                    <strong id="summaryBike">—</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Estación</span>
                    <strong id="summaryStation" class="text-end" style="max-width:60%">—</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted small">Tarifa</span>
                    <strong><?= format_money($tarifa) ?>/min</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 mb-3">
                    <span class="text-muted small">Costo estimado 30 min</span>
                    <strong><?= format_money($tarifa * 30) ?></strong>
                </div>

                <div class="note-box small mb-3">
                    <i class="bi bi-info-circle me-1"></i> La batería y el kilometraje se actualizan automáticamente cuando finalices el recorrido.
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-brand" type="submit" id="startBtn" disabled>
                        <i class="bi bi-play-circle me-1"></i> Iniciar alquiler
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= url('alquileres') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const search = document.getElementById('bikeSearch');
    const station = document.getElementById('stationFilter');
    const wrappers = Array.from(document.querySelectorAll('.bike-option-wrap'));
    const count = document.getElementById('bikeCount');
    const empty = document.getElementById('bikeEmpty');
    const startBtn = document.getElementById('startBtn');
    const summaryBike = document.getElementById('summaryBike');
    const summaryStation = document.getElementById('summaryStation');

    function filter() {
        const text = (search.value || '').toLowerCase().trim();
        const stationId = station.value;
        let visible = 0;
        wrappers.forEach(function (wrap) {
            const matchText = !text || wrap.dataset.search.indexOf(text) !== -1;
            const matchStation = stationId === '0' || wrap.dataset.station === stationId;
            const show = matchText && matchStation;
            wrap.classList.toggle('d-none', !show);
            if (show) visible++;
        });
        count.textContent = visible + ' disponibles';
        empty.classList.toggle('d-none', visible !== 0);
    }

    function selected() {
        return document.querySelector('input[name="id_bicicleta"]:checked');
    }

    function refreshSummary() {
        const input = selected();
        startBtn.disabled = !input;
        if (!input) {
            summaryBike.textContent = '—';
            summaryStation.textContent = '—';
            return;
        }
        const wrap = input.closest('.bike-option-wrap');
        summaryBike.textContent = input.closest('label').querySelector('.fw-semibold').textContent;
        summaryStation.textContent = wrap.querySelector('.bi-geo-alt').parentElement.textContent.trim();
    }

    if (search) search.addEventListener('input', filter);
    if (station) station.addEventListener('change', filter);
    document.querySelectorAll('input[name="id_bicicleta"]').forEach(function (input) {
        input.addEventListener('change', refreshSummary);
    });
    refreshSummary();
});
</script>
<?php endif; ?>
