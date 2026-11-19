<?php
/** @var array $station */
/** @var array $errors */
/** @var string $action */
/** @var bool $isEdit */
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p>Ubicación, capacidad y estado de la estación.</p>
    </div>
    <a href="<?= url('estaciones') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados.</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h2>Datos de la estación</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="codigo">Código</label>
                    <input type="text" class="form-control <?= isset($errors['codigo']) ? 'is-invalid' : '' ?>" id="codigo" name="codigo" value="<?= old($station, 'codigo') ?>" required>
                    <?php if (isset($errors['codigo'])): ?><div class="invalid-feedback"><?= e($errors['codigo']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre" value="<?= old($station, 'nombre') ?>" required>
                    <?php if (isset($errors['nombre'])): ?><div class="invalid-feedback"><?= e($errors['nombre']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="direccion">Dirección</label>
                    <input type="text" class="form-control <?= isset($errors['direccion']) ? 'is-invalid' : '' ?>" id="direccion" name="direccion" value="<?= old($station, 'direccion') ?>" placeholder="Calle, carrera, barrio...">
                    <?php if (isset($errors['direccion'])): ?><div class="invalid-feedback"><?= e($errors['direccion']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="latitud">Latitud</label>
                    <input type="number" step="0.0000001" class="form-control <?= isset($errors['latitud']) ? 'is-invalid' : '' ?>" id="latitud" name="latitud" value="<?= old($station, 'latitud') ?>" placeholder="5.5986" required>
                    <?php if (isset($errors['latitud'])): ?><div class="invalid-feedback"><?= e($errors['latitud']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="longitud">Longitud</label>
                    <input type="number" step="0.0000001" class="form-control <?= isset($errors['longitud']) ? 'is-invalid' : '' ?>" id="longitud" name="longitud" value="<?= old($station, 'longitud') ?>" placeholder="-75.8199" required>
                    <?php if (isset($errors['longitud'])): ?><div class="invalid-feedback"><?= e($errors['longitud']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="capacidad">Capacidad</label>
                    <input type="number" min="1" class="form-control <?= isset($errors['capacidad']) ? 'is-invalid' : '' ?>" id="capacidad" name="capacidad" value="<?= old($station, 'capacidad') ?>" required>
                    <?php if (isset($errors['capacidad'])): ?><div class="invalid-feedback"><?= e($errors['capacidad']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="energia_disp">Energía disponible (%)</label>
                    <input type="number" min="0" max="100" step="0.01" class="form-control <?= isset($errors['energia_disp']) ? 'is-invalid' : '' ?>" id="energia_disp" name="energia_disp" value="<?= old($station, 'energia_disp') ?>" required>
                    <?php if (isset($errors['energia_disp'])): ?><div class="invalid-feedback"><?= e($errors['energia_disp']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select <?= isset($errors['estado']) ? 'is-invalid' : '' ?>" id="estado" name="estado">
                        <?php foreach (['operativa' => 'Operativa', 'mantenimiento' => 'Mantenimiento', 'inactiva' => 'Inactiva'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($station['estado'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['estado'])): ?><div class="invalid-feedback"><?= e($errors['estado']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h2>Ayuda</h2></div>
            <div class="card-body">
                <div class="note-box mb-3"><i class="bi bi-geo-alt me-1"></i> Las coordenadas se guardan como tipo <strong>POINT</strong> en MySQL y alimentan el mapa Leaflet.</div>
                <p class="small text-muted mb-0">Puedes obtener latitud y longitud desde Google Maps: clic derecho sobre el punto → copiar coordenadas (lat, lng).</p>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-body d-grid gap-2">
                <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear estación' ?></button>
                <a class="btn btn-outline-secondary" href="<?= url('estaciones') ?>">Cancelar</a>
            </div>
        </div>
    </div>
</form>
