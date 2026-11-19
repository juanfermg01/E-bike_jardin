<?php
/** @var array $bike */
/** @var array $stations */
/** @var array $errors */
/** @var string $action */
/** @var bool $isEdit */
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $isEdit ? 'Actualiza los datos de la bicicleta.' : 'Registra una nueva bicicleta en la flota.' ?></p>
    </div>
    <a href="<?= url('bicicletas') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h2>Identificación</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="codigo_qr">Código QR</label>
                    <input type="text" class="form-control <?= isset($errors['codigo_qr']) ? 'is-invalid' : '' ?>" id="codigo_qr" name="codigo_qr" value="<?= old($bike, 'codigo_qr') ?>" required>
                    <?php if (isset($errors['codigo_qr'])): ?><div class="invalid-feedback"><?= e($errors['codigo_qr']) ?></div><?php endif; ?>
                    <div class="form-text">Se usa para identificar la bicicleta y generar el QR.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="num_serie">Número de serie</label>
                    <input type="text" class="form-control <?= isset($errors['num_serie']) ? 'is-invalid' : '' ?>" id="num_serie" name="num_serie" value="<?= old($bike, 'num_serie') ?>" required>
                    <?php if (isset($errors['num_serie'])): ?><div class="invalid-feedback"><?= e($errors['num_serie']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="modelo">Modelo</label>
                    <input type="text" class="form-control <?= isset($errors['modelo']) ? 'is-invalid' : '' ?>" id="modelo" name="modelo" value="<?= old($bike, 'modelo') ?>" placeholder="Ej: Bafang Urban 2025" required>
                    <?php if (isset($errors['modelo'])): ?><div class="invalid-feedback"><?= e($errors['modelo']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="estado">Estado</label>
                    <select class="form-select <?= isset($errors['estado']) ? 'is-invalid' : '' ?>" id="estado" name="estado">
                        <?php foreach (['disponible' => 'Disponible', 'en_uso' => 'En uso', 'mantenimiento' => 'Mantenimiento', 'baja' => 'Baja'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($bike['estado'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['estado'])): ?><div class="invalid-feedback"><?= e($errors['estado']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Ubicación y uso</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="estacion_act_fk">Estación actual</label>
                    <select class="form-select <?= isset($errors['estacion_act_fk']) ? 'is-invalid' : '' ?>" id="estacion_act_fk" name="estacion_act_fk">
                        <option value="">Sin asignar</option>
                        <?php foreach ($stations as $s): ?>
                            <option value="<?= (int)$s['id_estacion'] ?>" <?= (string)($bike['estacion_act_fk'] ?? '') === (string)$s['id_estacion'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['estacion_act_fk'])): ?><div class="invalid-feedback"><?= e($errors['estacion_act_fk']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="kilometraje">Kilometraje (km)</label>
                    <input type="number" step="0.01" min="0" class="form-control <?= isset($errors['kilometraje']) ? 'is-invalid' : '' ?>" id="kilometraje" name="kilometraje" value="<?= old($bike, 'kilometraje') ?>" required>
                    <?php if (isset($errors['kilometraje'])): ?><div class="invalid-feedback"><?= e($errors['kilometraje']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ult_manten_date">Último mantenimiento</label>
                    <input type="date" class="form-control <?= isset($errors['ult_manten_date']) ? 'is-invalid' : '' ?>" id="ult_manten_date" name="ult_manten_date" value="<?= e(substr((string)($bike['ult_manten_date'] ?? ''), 0, 10)) ?>">
                    <?php if (isset($errors['ult_manten_date'])): ?><div class="invalid-feedback"><?= e($errors['ult_manten_date']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="prox_manten_date">Próximo mantenimiento</label>
                    <input type="date" class="form-control <?= isset($errors['prox_manten_date']) ? 'is-invalid' : '' ?>" id="prox_manten_date" name="prox_manten_date" value="<?= e(substr((string)($bike['prox_manten_date'] ?? ''), 0, 10)) ?>">
                    <?php if (isset($errors['prox_manten_date'])): ?><div class="invalid-feedback"><?= e($errors['prox_manten_date']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h2>Batería</h2></div>
            <div class="card-body">
                <label class="form-label" for="nivel_bateria">Nivel de batería (%)</label>
                <div class="d-flex align-items-center gap-2">
                    <input type="range" class="form-range" id="batteryRange" min="0" max="100" step="1" value="<?= (float)($bike['nivel_bateria'] ?? 100) ?>">
                    <input type="number" min="0" max="100" step="0.01" class="form-control <?= isset($errors['nivel_bateria']) ? 'is-invalid' : '' ?>" id="nivel_bateria" name="nivel_bateria" value="<?= old($bike, 'nivel_bateria', '100') ?>" style="max-width:90px" required>
                </div>
                <?php if (isset($errors['nivel_bateria'])): ?><div class="text-danger small mt-1"><?= e($errors['nivel_bateria']) ?></div><?php endif; ?>
                <div class="note-box mt-3 small"><i class="bi bi-info-circle me-1"></i> El sistema descuenta batería automáticamente al finalizar cada alquiler.</div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar bicicleta' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= url('bicicletas') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const range = document.getElementById('batteryRange');
    const number = document.getElementById('nivel_bateria');
    if (!range || !number) return;
    range.addEventListener('input', function () { number.value = range.value; });
    number.addEventListener('input', function () { range.value = number.value; });
});
</script>
