<?php
$bodyClass = 'app-body';
require APP_PATH . '/views/layouts/head.php';
?>
    <div class="app-shell">
        <?php require APP_PATH . '/views/layouts/sidebar.php'; ?>
        <div class="app-main">
            <?php require APP_PATH . '/views/layouts/topbar.php'; ?>
            <main class="app-content">
                <?= $content ?>
            </main>
            <footer class="app-footer">
                <span>© <?= date('Y') ?> <?= e(APP_NAME) ?> · Sistema de alquiler de bicicletas eléctricas</span>
                <span class="text-muted d-none d-md-inline">v<?= e(APP_VERSION) ?></span>
            </footer>
        </div>
    </div>
    <?php require APP_PATH . '/views/layouts/scripts.php'; ?>
</body>
</html>
