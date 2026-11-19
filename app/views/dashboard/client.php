<?php
/** @var array|null $activeRental */
/** @var array $totals */
/** @var float $spent */
/** @var int $available */
/** @var int $stationsCount */
/** @var array $recommended */
/** @var array $recentRentals */
/** @var array $chartData */
$u = Auth::user();
$minutesActive = $activeRental ? max(1, (int)ceil((time() - strtotime($activeRental['fecha_inicio'])) / 60)) : 0;
?>
<div class="page-head">
    <div>
        <h1>Hola, <?= e(explode(' ', $u['nombre'])[0]) ?></h1>
        <p>Tu movilidad eléctrica en Jardín, lista cuando tú estés.</p>
    </div>
    <?php if (!$activeRental): ?>
        <a href="<?= url('alquileres/iniciar') ?>" class="btn btn-brand"><i class="bi bi-lightning-charge-fill me-1"></i> Alquilar una bicicleta</a>
    <?php endif; ?>
</div>

<?php if ($activeRental): ?>
    <div class="timer-card mb-3" data-timer-start="<?= e($activeRental['fecha_inicio']) ?>" data-timer-rate="<?= (float)$activeRental['tarifa_por_minuto'] ?>">
        <div class="row align-items-center g-3 position-relative" style="z-index:1">
            <div class="col-lg-7">
                <div class="timer-label mb-1"><span class="spinner-grow spinner-grow-sm me-1"></span> Alquiler en curso</div>
                <div class="d-flex align-items-baseline gap-3 flex-wrap">
                    <div class="timer-display" data-timer-elapsed><?= str_pad((string)intdiv($minutesActive, 60), 2, '0', STR_PAD_LEFT) ?>:<?= str_pad((string)($minutesActive % 60), 2, '0', STR_PAD_LEFT) ?>:00</div>
                    <div class="timer-cost" data-timer-cost><?= format_money($minutesActive * (float)$activeRental['tarifa_por_minuto']) ?></div>
                </div>
                <div class="d-flex gap-3 flex-wrap mt-2 small" style="color:rgba(233,246,239,.8)">
                    <span><i class="bi bi-bicycle me-1"></i><?= e($activeRental['codigo_qr']) ?> · <?= e($activeRental['modelo']) ?></span>
                    <span><i class="bi bi-battery-half me-1"></i><?= (float)$activeRental['nivel_bateria'] ?>% batería</span>
                    <span><i class="bi bi-geo-alt me-1"></i><?= e($activeRental['estacion_origen']) ?></span>
                </div>
            </div>
            <div class="col-lg-5 text-lg-end">
                <a href="<?= url('alquileres/finalizar/' . $activeRental['id_alquiler']) ?>" class="btn btn-lime">
                    <i class="bi bi-stop-circle me-1"></i> Finalizar alquiler
                </a>
                <div class="small mt-2" style="color:rgba(233,246,239,.65)">Inició <?= format_date($activeRental['fecha_inicio'], 'd/m/Y H:i') ?></div>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="card mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon mb-0"><i class="bi bi-bicycle"></i></div>
                <div>
                    <h2 class="h6 mb-1">¿Listo para tu próximo recorrido?</h2>
                    <p class="text-muted small mb-0">Hay <?= (int)$available ?> bicicletas disponibles en <?= (int)$stationsCount ?> estaciones operativas.</p>
                </div>
            </div>
            <a href="<?= url('alquileres/iniciar') ?>" class="btn btn-brand"><i class="bi bi-play-circle me-1"></i> Iniciar alquiler</a>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-label">Viajes completados</div>
            <div class="stat-value"><?= (int)$totals['total'] ?></div>
            <div class="stat-hint">Historial total de recorridos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <div class="stat-label">Total pagado</div>
            <div class="stat-value"><?= format_money($spent) ?></div>
            <div class="stat-hint">Pagos aprobados</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-label">Tiempo en bicicleta</div>
            <div class="stat-value"><?= format_minutes((int)$totals['minutos']) ?></div>
            <div class="stat-hint">Suma de tus recorridos</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-battery-charging"></i></div>
            <div class="stat-label">Bicis disponibles</div>
            <div class="stat-value"><?= (int)$available ?></div>
            <div class="stat-hint">En <?= (int)$stationsCount ?> estaciones</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header">
                <h2>Recomendadas para ti</h2>
                <span class="chip"><i class="bi bi-battery-full"></i> Mayor batería</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php if (!$recommended): ?>
                        <div class="col-12"><div class="empty-state"><i class="bi bi-bicycle"></i><h3>No hay bicicletas disponibles</h3><p class="small">Intenta de nuevo en unos minutos.</p></div></div>
                    <?php else: foreach ($recommended as $b):
                        $bat = (float)$b['nivel_bateria'];
                        $barClass = $bat < 20 ? 'low' : ($bat < 50 ? 'medium' : '');
                    ?>
                        <div class="col-sm-6">
                            <div class="bike-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="bike-icon"><i class="bi bi-bicycle"></i></div>
                                    <?= badge_estado($b['estado']) ?>
                                </div>
                                <div>
                                    <h4><?= e($b['codigo_qr']) ?></h4>
                                    <div class="bike-meta"><?= e($b['modelo']) ?></div>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-muted">Batería</span><strong><?= $bat ?>%</strong>
                                    </div>
                                    <div class="battery-bar <?= $barClass ?>"><span style="width: <?= max(3, $bat) ?>%"></span></div>
                                </div>
                                <div class="bike-meta"><i class="bi bi-geo-alt me-1"></i><?= e($b['estacion']) ?></div>
                                <a href="<?= url('alquileres/iniciar?bici=' . $b['id_bicicleta']) ?>" class="btn btn-soft w-100">
                                    <i class="bi bi-lightning-charge me-1"></i> Alquilar
                                </a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><h2>Estaciones cerca</h2><a class="btn btn-sm btn-soft" href="<?= url('mapa') ?>">Mapa completo</a></div>
            <div class="card-body p-2">
                <div class="map-mini" style="height:300px">
                    <div id="map" data-mode="landing"
                         data-stations-url="<?= url('api/estaciones') ?>"
                         data-chargers-url="<?= url('api/electrolineras') ?>"></div>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="note-box"><i class="bi bi-info-circle me-1"></i> Toca un marcador para ver la estación y calcular tu ruta.</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2>Mis viajes por mes</h2></div>
            <div class="card-body"><div class="chart-box" style="height:240px"><canvas id="chartAlquileres"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><h2>Mi gasto por mes</h2></div>
            <div class="card-body"><div class="chart-box" style="height:240px"><canvas id="chartIngresos"></canvas></div></div>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><h2>Mi historial reciente</h2><a href="<?= url('alquileres') ?>" class="btn btn-sm btn-soft">Ver todo</a></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Bicicleta</th><th>Estación</th><th>Inicio</th><th>Duración</th><th>Costo</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php if (!$recentRentals): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">Aún no tienes alquileres. ¡Empieza tu primer recorrido!</td></tr>
            <?php else: foreach ($recentRentals as $r): ?>
                <tr>
                    <td class="text-muted">#<?= (int)$r['id_alquiler'] ?></td>
                    <td><?= e($r['codigo_qr']) ?></td>
                    <td><?= e($r['estacion_origen']) ?></td>
                    <td><?= format_date($r['fecha_inicio'], 'd/m/Y H:i') ?></td>
                    <td><?= format_minutes($r['duracion_minutos'] ? (int)$r['duracion_minutos'] : null) ?></td>
                    <td><?= $r['costo_total'] !== null ? format_money($r['costo_total']) : '—' ?></td>
                    <td><?= badge_estado($r['estado']) ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-soft" href="<?= url('alquileres/ver/' . $r['id_alquiler']) ?>">Ver</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>window.CHART_DATA = <?= json_encode($chartData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
