<?php
$bodyClass = 'guest-body';
require APP_PATH . '/views/layouts/head.php';
$u = Auth::user();
?>
<div class="guest-shell">
    <nav class="guest-nav">
        <a class="brand" href="<?= url('') ?>">
            <img src="<?= asset('img/logo.svg') ?>" alt="E-Bike Jardín" width="42" height="42">
            <span><strong>E-Bike</strong> Jardín</span>
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if ($u): ?>
                <a class="btn btn-sm btn-brand" href="<?= url('dashboard') ?>"><i class="bi bi-speedometer2 me-1"></i> Ir al panel</a>
            <?php else: ?>
                <a class="btn btn-sm btn-ghost" href="<?= url('login') ?>">Iniciar sesión</a>
                <a class="btn btn-sm btn-brand" href="<?= url('registro') ?>">Registrarme</a>
            <?php endif; ?>
        </div>
    </nav>

    <main class="guest-content">
        <?= $content ?>
    </main>

    <footer class="guest-footer">
        <span>© <?= date('Y') ?> <?= e(APP_NAME) ?> · Jardín, Antioquia</span>
        <span>Movilidad eléctrica para un pueblo que respira.</span>
    </footer>
</div>
<?php require APP_PATH . '/views/layouts/scripts.php'; ?>
</body>
</html>
