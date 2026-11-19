<?php
$u = Auth::user();
$nivel = (int)($u['nivel_acceso'] ?? 3);
$iniciales = '';
if ($u) {
    $partes = preg_split('/\s+/', trim($u['nombre']));
    $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
}
?>
<header class="topbar">
    <button class="btn btn-icon sidebar-toggle" id="sidebarToggle" aria-label="Abrir menú">
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-title">
        <h1><?= e($pageTitle ?? 'Panel') ?></h1>
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('dashboard') ?>">Inicio</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle ?? 'Panel') ?></li>
            </ol>
        </nav>
    </div>

    <div class="topbar-actions">
        <?php if ($nivel === 3): ?>
            <a href="<?= url('alquileres/iniciar') ?>" class="btn btn-brand btn-sm d-none d-sm-inline-flex">
                <i class="bi bi-lightning-charge-fill me-1"></i> Alquilar bici
            </a>
        <?php endif; ?>
        <span class="topbar-date d-none d-xl-inline-flex">
            <i class="bi bi-calendar3"></i> <?= ucfirst(strftime_es()) ?>
        </span>

        <div class="dropdown">
            <button class="btn user-chip dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="avatar avatar-sm"><?= e($iniciales ?: 'EB') ?></span>
                <span class="d-none d-md-flex flex-column text-start">
                    <strong><?= e(explode(' ', $u['nombre'] ?? 'Usuario')[0]) ?></strong>
                    <small><?= e($u['nombre_rol'] ?? '') ?></small>
                </span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li><h6 class="dropdown-header"><?= e($u['email'] ?? '') ?></h6></li>
                <li><a class="dropdown-item" href="<?= url('perfil') ?>"><i class="bi bi-person me-2"></i>Mi perfil</a></li>
                <li><a class="dropdown-item" href="<?= url('mapa') ?>"><i class="bi bi-geo-alt me-2"></i>Mapa</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post" action="<?= url('logout') ?>" data-confirm="Cerrar sesión" data-text="¿Seguro que quieres salir de E-Bike Jardín?" data-icon="question">
                        <?= csrf_field() ?>
                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
