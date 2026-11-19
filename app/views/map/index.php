<?php
/** @var array $stations */
/** @var array $totals */
?>
<div class="page-head">
    <div>
        <h1>Mapa y estaciones</h1>
        <p>Estaciones de alquiler de E-Bike Jardín y puntos de carga del proyecto, en un solo mapa.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('alquileres/iniciar') ?>" class="btn btn-brand"><i class="bi bi-lightning-charge me-1"></i> Alquilar</a>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    <span class="chip"><i class="bi bi-signpost-split"></i> <?= (int)$totals['estaciones'] ?> estaciones E-Bike</span>
    <span class="chip"><i class="bi bi-bicycle"></i> <?= (int)$totals['disponibles'] ?> bicis disponibles</span>
    <span class="chip"><i class="bi bi-ev-station"></i> 46 puntos de carga</span>
    <span class="chip"><i class="bi bi-geo"></i> Jardín, Antioquia</span>
</div>

<div class="map-layout">
    <div class="map-panel">
        <div class="card">
            <div class="card-body">
                <div class="map-search mb-3">
                    <i class="bi bi-search"></i>
                    <input type="search" class="form-control" id="stationSearch" placeholder="Buscar estación o dirección...">
                </div>

                <div class="d-flex gap-3 flex-wrap mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="filterEstaciones" checked>
                        <label class="form-check-label small" for="filterEstaciones">Estaciones E-Bike</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="filterCargadores">
                        <label class="form-check-label small" for="filterCargadores">Puntos de carga</label>
                    </div>
                </div>

                <label class="form-label d-flex justify-content-between" for="rangeKm">
                    <span>Autonomía estimada</span>
                    <strong id="rangeLabel">20 km</strong>
                </label>
                <input type="range" class="form-range" id="rangeKm" min="5" max="80" step="5" value="20">

                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-brand btn-sm flex-grow-1" id="locateBtn"><i class="bi bi-crosshair me-1"></i> Mi ubicación</button>
                    <button class="btn btn-outline-brand btn-sm" id="resetView" title="Restablecer vista"><i class="bi bi-arrow-counterclockwise"></i></button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>Estación más cercana</h2></div>
            <div class="card-body" id="nearestStation">
                <p class="text-muted small mb-0"><i class="bi bi-crosshair me-1"></i>Activa tu ubicación para calcular la estación más cercana.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2>Estaciones E-Bike</h2>
                <span class="badge text-bg-success badge-soft"><?= count($stations) ?></span>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2" id="reachableCount">Activa tu ubicación para ver cuáles están a tu alcance.</p>
                <div class="station-list" id="stationList">
                    <?php foreach ($stations as $s): ?>
                        <div class="station-item" data-station-id="<?= (int)$s['id_estacion'] ?>">
                            <h4><span><?= e($s['nombre']) ?></span><span class="chip"><?= (int)$s['bicis_disponibles'] ?> libres</span></h4>
                            <p><i class="bi bi-geo-alt me-1"></i><?= e($s['direccion']) ?></p>
                            <div class="meta">
                                <span><i class="bi bi-bicycle me-1"></i><?= (int)$s['bicis_totales'] ?> bicis</span>
                                <span><i class="bi bi-battery-charging me-1"></i><?= (float)$s['energia_disp'] ?>%</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="routing-panel d-none" id="routePanel">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0"><i class="bi bi-signpost-2 me-1"></i> Ruta calculada</h2>
                <span class="chip">OSRM</span>
            </div>
            <div id="routePanelBody"></div>
        </div>
    </div>

    <div class="map-wrap">
        <div id="map"
             data-mode="full"
             data-stations-url="<?= url('api/estaciones') ?>"
             data-chargers-url="<?= url('api/electrolineras') ?>"></div>
        <div class="map-legend">
            <span><i class="legend-dot" style="background:#0b6e4f"></i> Estación E-Bike</span>
            <span><i class="legend-dot" style="background:#0ea5e9"></i> Punto de carga</span>
            <span><i class="legend-dot" style="background:#f59e0b"></i> Tu ubicación</span>
        </div>
    </div>
</div>

<div class="note-box mt-3">
    <i class="bi bi-info-circle me-1"></i>
    Los puntos de carga provienen del dataset del proyecto (48 registros, 46 con coordenadas). El cálculo de rutas usa el servicio público OSRM; si no hay internet, el mapa sigue mostrando las estaciones y puedes abrir la ruta en Google Maps.
</div>
