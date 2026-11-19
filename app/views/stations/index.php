<?php
/** @var array $stations */
/** @var array $totals */
/** @var bool $isAdmin */
?>
<div class="page-head">
    <div>
        <h1>Estaciones</h1>
        <p>Puntos de recogida y entrega de bicicletas en Jardín.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('mapa') ?>" class="btn btn-outline-brand"><i class="bi bi-geo-alt me-1"></i> Ver mapa</a>
        <?php if ($isAdmin): ?>
            <a href="<?= url('estaciones/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Nueva estación</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="stat-icon"><i class="bi bi-signpost-split"></i></div><div class="stat-label">Estaciones</div><div class="stat-value"><?= (int)$totals['total'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card lime"><div class="stat-icon"><i class="bi bi-bicycle"></i></div><div class="stat-label">Bicis en estaciones</div><div class="stat-value"><?= (int)$totals['flota'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card info"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div class="stat-label">Disponibles</div><div class="stat-value"><?= (int)$totals['disponibles'] ?></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="stat-card warn"><div class="stat-icon"><i class="bi bi-battery-charging"></i></div><div class="stat-label">Energía promedio</div><div class="stat-value"><?= number_format((float)$totals['energia'] / max(1, (int)$totals['total']), 1, ',', '.') ?>%</div></div></div>
</div>

<div class="row g-3">
    <?php foreach ($stations as $s):
        $ocupacion = (int)$s['capacidad'] > 0 ? min(100, round(((int)$s['bicis_totales'] / (int)$s['capacidad']) * 100)) : 0;
        $energia = (float)$s['energia_disp'];
    ?>
        <div class="col-md-6 col-xxl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex gap-2 align-items-center">
                            <span class="bike-icon"><i class="bi bi-signpost-split"></i></span>
                            <div>
                                <h2 class="h6 mb-0"><?= e($s['nombre']) ?></h2>
                                <small class="text-muted"><?= e($s['codigo']) ?></small>
                            </div>
                        </div>
                        <?= badge_estado($s['estado']) ?>
                    </div>

                    <p class="small text-muted mb-2"><i class="bi bi-geo-alt me-1"></i><?= e($s['direccion']) ?></p>

                    <div class="row g-2 mb-2">
                        <div class="col-6"><div class="stat-mini"><strong><?= (int)$s['bicis_disponibles'] ?> / <?= (int)$s['bicis_totales'] ?></strong><span>Bicis libres</span></div></div>
                        <div class="col-6"><div class="stat-mini"><strong><?= (int)$s['capacidad'] ?></strong><span>Capacidad</span></div></div>
                    </div>

                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Ocupación</span><strong><?= $ocupacion ?>%</strong></div>
                    <div class="progress-thin mb-3"><div class="progress-bar bg-success" style="width: <?= $ocupacion ?>%"></div></div>

                    <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Energía</span><strong><?= $energia ?>%</strong></div>
                    <div class="battery-bar <?= $energia < 30 ? 'low' : ($energia < 60 ? 'medium' : '') ?> mb-3"><span style="width: <?= max(3, $energia) ?>%"></span></div>

                    <div class="d-flex gap-2">
                        <a href="<?= url('mapa') ?>" class="btn btn-sm btn-soft flex-grow-1"><i class="bi bi-map me-1"></i> Mapa</a>
                        <?php if ($isAdmin): ?>
                            <a href="<?= url('estaciones/editar/' . $s['id_estacion']) ?>" class="btn btn-sm btn-soft" title="Editar"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= url('estaciones/' . $s['id_estacion'] . '/eliminar') ?>"
                                  data-confirm="Eliminar estación" data-text="Se eliminará <?= e($s['nombre']) ?>. Solo es posible si no tiene bicicletas asignadas.">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
