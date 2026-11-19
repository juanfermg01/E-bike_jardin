<?php
$u = Auth::user();
$nivel = (int)($u['nivel_acceso'] ?? 3);
$iniciales = '';
if ($u) {
    $partes = preg_split('/\s+/', trim($u['nombre']));
    $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
}
?>
<aside class="sidebar" id="sidebarMenu" aria-label="Menú principal">
    <div class="sidebar-brand">
        <a href="<?= url('dashboard') ?>" class="d-flex align-items-center gap-2 text-decoration-none">
            <img src="<?= asset('img/logo.svg') ?>" alt="Logo" width="44" height="44">
            <span class="brand-text"><strong>E-Bike</strong><span>Jardín</span></span>
        </a>
        <button class="btn btn-sm text-white d-lg-none" id="sidebarClose" aria-label="Cerrar menú">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <div class="sidebar-scroll">
        <div class="nav-section">Principal</div>
        <nav class="sidebar-nav">
            <a class="nav-link-item <?= is_active('dashboard') ?>" href="<?= url('dashboard') ?>">
                <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
            </a>
            <a class="nav-link-item <?= is_active('mapa') ?>" href="<?= url('mapa') ?>">
                <i class="bi bi-geo-alt"></i><span>Mapa y estaciones</span>
            </a>
        </nav>

        <div class="nav-section">Operación</div>
        <nav class="sidebar-nav">
            <a class="nav-link-item <?= is_active('alquileres') ?>" href="<?= url('alquileres') ?>">
                <i class="bi bi-lightning-charge"></i><span><?= $nivel === 3 ? 'Mis alquileres' : 'Alquileres' ?></span>
            </a>
            <a class="nav-link-item <?= is_active('bicicletas') ?>" href="<?= url('bicicletas') ?>">
                <i class="bi bi-bicycle"></i><span>Bicicletas</span>
            </a>
            <a class="nav-link-item <?= is_active('estaciones') ?>" href="<?= url('estaciones') ?>">
                <i class="bi bi-signpost-split"></i><span>Estaciones</span>
            </a>
            <?php if ($nivel <= 2): ?>
                <a class="nav-link-item <?= is_active('mantenimientos') ?>" href="<?= url('mantenimientos') ?>">
                    <i class="bi bi-tools"></i><span>Mantenimientos</span>
