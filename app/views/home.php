<?php /** @var array $stats */ ?>
<section class="hero">
    <div class="hero-inner">
        <div>
            <span class="hero-badge"><i class="bi bi-geo-alt-fill"></i> Jardín, Antioquia</span>
            <h1>La forma más <span>limpia</span> de moverte por Jardín.</h1>
            <p class="lead">Bicicletas eléctricas listas para recorrer el pueblo, sus cafés y sus miradores. Reserva en segundos y devuélvela en la estación que prefieras.</p>
            <div class="hero-actions">
                <a class="btn btn-lime btn-lg" href="<?= url('registro') ?>"><i class="bi bi-bicycle me-1"></i> Empezar a rodar</a>
                <a class="btn btn-ghost btn-lg" href="<?= url('login') ?>">Ya tengo cuenta</a>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><strong><?= (int)$stats['bicis'] ?></strong><small>Bicis disponibles</small></div>
                <div class="hero-stat"><strong><?= (int)$stats['estaciones'] ?></strong><small>Estaciones activas</small></div>
                <div class="hero-stat"><strong><?= number_format((int)$stats['alquileres'], 0, ',', '.') ?></strong><small>Viajes realizados</small></div>
                <div class="hero-stat"><strong>$150</strong><small>COP por minuto</small></div>
            </div>
        </div>

        <div class="hero-art">
            <div class="map-mini">
                <div id="map"
                     data-mode="landing"
                     data-stations-url="<?= url('api/estaciones') ?>"
                     data-chargers-url="<?= url('api/electrolineras') ?>"></div>
            </div>
            <div class="art-caption">
                <span><span class="dot"></span> Estaciones de alquiler en tiempo real</span>
                <a class="text-white text-decoration-none small" href="<?= url('login') ?>">Ver mapa completo <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</section>

<section class="features">
    <div class="section-title">
        <span class="chip"><i class="bi bi-stars"></i> Diseñado para Jardín</span>
        <h2 class="mt-2">Todo lo que necesitas para moverte sin ruido</h2>
        <p>Un sistema completo de alquiler con estaciones, control de batería, tarifas automáticas y pagos simulados.</p>
    </div>

    <div class="feature-grid">
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-lightning-charge"></i></div>
            <h3>Alquiler en segundos</h3>
            <p>Elige una bicicleta disponible, confirma el inicio y recibe tu código de alquiler al instante.</p>
        </article>
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-battery-half"></i></div>
            <h3>Batería siempre visible</h3>
            <p>Consulta el nivel de batería de cada bici antes de salir y recibe alertas cuando está baja.</p>
        </article>
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-receipt"></i></div>
            <h3>Tarifa transparente</h3>
            <p>Pagas solo por minuto utilizado. Al finalizar obtienes tu recibo digital y el detalle del pago.</p>
        </article>
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-geo-alt"></i></div>
            <h3>Mapa interactivo</h3>
            <p>Ubica estaciones de alquiler y puntos de carga, calcula rutas y encuentra la más cercana.</p>
        </article>
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
            <h3>Cuenta segura</h3>
            <p>Contraseñas cifradas con bcrypt, control de roles y protección CSRF en todos los formularios.</p>
        </article>
        <article class="feature-card">
            <div class="feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <h3>Panel administrativo</h3>
            <p>Métricas de ingresos, ocupación, energía y mantenimiento para tomar mejores decisiones.</p>
        </article>
    </div>
</section>
