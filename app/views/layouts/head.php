<?php
/** @var string $pageTitle */
$pageTitle = $pageTitle ?? APP_NAME;
$assets = $assets ?? [];
$u = Auth::user();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b6e4f">
    <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('img/logo.svg') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
    <?php if (!empty($assets['leaflet'])): ?>
        <link rel="stylesheet" href="<?= asset('vendor/leaflet/leaflet.css') ?>">
        <link rel="stylesheet" href="<?= asset('vendor/leaflet-routing/leaflet-routing-machine.css') ?>">
    <?php endif; ?>
</head>
<body class="<?= e($bodyClass ?? '') ?>">
