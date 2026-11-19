<?php
/** @var array $assets */
$assets = $assets ?? [];
$flashMessages = flashes();
?>
<script>
window.APP = {
    baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
    flash: <?= json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
};
</script>
<script src="<?= asset('vendor/bootstrap/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= asset('vendor/sweetalert2/sweetalert2.all.min.js') ?>"></script>
<?php if (!empty($assets['chart'])): ?>
    <script src="<?= asset('vendor/chartjs/chart.umd.min.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($assets['leaflet'])): ?>
    <script src="<?= asset('vendor/leaflet/leaflet.js') ?>"></script>
    <script src="<?= asset('vendor/leaflet-routing/leaflet-routing-machine.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($assets['qrcode'])): ?>
    <script src="<?= asset('vendor/qrcode/qrcode.min.js') ?>"></script>
<?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (!empty($assets['map'])): ?>
    <script src="<?= asset('js/map.js') ?>"></script>
<?php endif; ?>
<?php if (!empty($assets['charts'])): ?>
    <script src="<?= asset('js/charts.js') ?>"></script>
<?php endif; ?>
</body>
</html>
