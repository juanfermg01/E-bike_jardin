<?php
/** @var array $stats */
/** @var array $activeRentals */
/** @var array $recentRentals */
/** @var array $lowBattery */
/** @var array $upcomingMaintenance */
/** @var array $stations */
/** @var array $chartData */
$u = Auth::user();
?>
<div class="page-head">
    <div>
        <h1>Hola, <?= e(explode(' ', $u['nombre'])[0]) ?></h1>
        <p>Este es el pulso de E-Bike Jardín hoy. Revisa alquileres, batería y mantenimiento.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= url('alquileres') ?>" class="btn btn-outline-brand"><i class="bi bi-list-check me-1"></i> Alquileres</a>
        <a href="<?= url('bicicletas/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Nueva bicicleta</a>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-label">Ingresos del mes</div>
            <div class="stat-value"><?= format_money($stats['ingresos_mes']) ?></div>
            <div class="stat-hint"><?= (int)$stats['alquileres_mes'] ?> alquileres iniciados este mes</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-lightning-charge"></i></div>
            <div class="stat-label">Alquileres activos</div>
            <div class="stat-value"><?= (int)$stats['alquileres_activos'] ?></div>
            <div class="stat-hint"><?= (int)$stats['en_uso'] ?> bicicletas en uso ahora</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-bicycle"></i></div>
            <div class="stat-label">Flota disponible</div>
            <div class="stat-value"><?= (int)$stats['disponibles'] ?> <span class="fs-6 text-muted fw-normal">/ <?= (int)$stats['bicis_total'] ?></span></div>
            <div class="stat-hint"><?= (int)$stats['mantenimiento'] ?> en mantenimiento</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-battery-charging"></i></div>
            <div class="stat-label">Energía del mes</div>
            <div class="stat-value"><?= format_money($stats['energia_mes']) ?></div>
            <div class="stat-hint"><?= (int)$stats['estaciones'] ?> estaciones · <?= (int)$stats['clientes'] ?> clientes</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h2>Ingresos y alquileres</h2>
                    <div class="text-muted small">Comportamiento de los últimos 6 meses</div>
                </div>
                <span class="chip"><i class="bi bi-graph-up-arrow"></i> <?= format_money($stats['ingresos_mes']) ?> este mes</span>
            </div>
            <div class="card-body">
                <div class="chart-box"><canvas id="chartIngresos"></canvas></div>
                <div class="chart-box mt-4" style="height:180px"><canvas id="chartAlquileres"></canvas></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><h2>Estado de la flota</h2></div>
            <div class="card-body">
                <div class="chart-box"><canvas id="chartEstados"></canvas></div>
                <div class="row g-2 mt-2">
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['disponibles'] ?></strong><span>Disponibles</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['en_uso'] ?></strong><span>En uso</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['mantenimiento'] ?></strong><span>Mantenimiento</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['estaciones'] ?></strong><span>Estaciones</span></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <h2>Alquileres activos</h2>
                <a href="<?= url('alquileres') ?>" class="btn btn-sm btn-soft">Ver todos <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Cliente</th><th>Bicicleta</th><th>Inicio</th><th>Tiempo</th><th>Costo est.</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php if (!$activeRentals): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4"><i class="bi bi-emoji-smile me-1"></i> No hay alquileres activos en este momento.</td></tr>
                    <?php else: foreach ($activeRentals as $r):
