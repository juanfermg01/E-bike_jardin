<?php
/** @var int $code */
/** @var string $message */
$icon = match ((int)$code) {
    403 => 'bi-shield-lock',
    404 => 'bi-compass',
    419 => 'bi-hourglass-split',
    default => 'bi-exclamation-octagon',
};
?>
<div class="d-flex align-items-center justify-content-center py-5">
    <div class="card text-center border-0 shadow-sm" style="max-width:520px;width:100%;border-radius:24px">
        <div class="card-body p-4 p-md-5">
            <div class="stat-icon mx-auto mb-3" style="width:76px;height:76px;font-size:2rem">
                <i class="bi <?= $icon ?>"></i>
            </div>
            <div class="display-4 fw-bold text-brand mb-2"><?= (int)$code ?></div>
            <h1 class="h4 mb-2"><?= e($message) ?></h1>
            <p class="text-muted small mb-4">
                <?php if ((int)$code === 404): ?>
                    La dirección que buscas no existe o fue movida dentro del sistema.
                <?php elseif ((int)$code === 403): ?>
                    Tu rol actual no tiene permisos para ver esta sección.
                <?php elseif ((int)$code === 419): ?>
                    El formulario expiró por seguridad. Recarga la página e inténtalo de nuevo.
                <?php else: ?>
                    Ocurrió un problema inesperado. Intenta de nuevo en unos segundos.
                <?php endif; ?>
            </p>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="<?= url(Auth::check() ? 'dashboard' : '') ?>" class="btn btn-brand">
                    <i class="bi bi-house-door me-1"></i> Volver al inicio
                </a>
                <?php if (!Auth::check()): ?>
                    <a href="<?= url('login') ?>" class="btn btn-outline-brand">Iniciar sesión</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
