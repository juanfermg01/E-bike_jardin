<?php
/** @var array $item */
/** @var array $bikes */
/** @var array $errors */
/** @var string $action */
/** @var bool $isEdit */
$inicioLocal = !empty($item['fecha_inicio']) && strtotime((string)$item['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime((string)$item['fecha_inicio'])) : '';
$finLocal = !empty($item['fecha_fin']) && strtotime((string)$item['fecha_fin']) ? date('Y-m-d\TH:i', strtotime((string)$item['fecha_fin'])) : '';
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $isEdit ? 'Actualiza el estado, las fechas o el costo del mantenimiento.' : 'Registra una revisión o reparación de la flota.' ?></p>
    </div>
    <a href="<?= url('mantenimientos') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h2>Detalle del mantenimiento</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="id_bicicleta_fk">Bicicleta</label>
                    <select class="form-select <?= isset($errors['id_bicicleta_fk']) ? 'is-invalid' : '' ?>" id="id_bicicleta_fk" name="id_bicicleta_fk" required>
                        <option value="">Selecciona una bicicleta</option>
                        <?php foreach ($bikes as $b): ?>
                            <option value="<?= (int)$b['id_bicicleta'] ?>" <?= (string)($item['id_bicicleta_fk'] ?? '') === (string)$b['id_bicicleta'] ? 'selected' : '' ?>>
                                <?= e($b['codigo_qr']) ?> · <?= e($b['modelo']) ?> (<?= e($b['estado']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['id_bicicleta_fk'])): ?><div class="invalid-feedback"><?= e($errors['id_bicicleta_fk']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="tipo_manten">Tipo de mantenimiento</label>
                    <input type="text" class="form-control <?= isset($errors['tipo_manten']) ? 'is-invalid' : '' ?>" id="tipo_manten" name="tipo_manten"
                           value="<?= old($item, 'tipo_manten') ?>" list="tiposMantenimiento" placeholder="Ej: Preventivo" required>
                    <datalist id="tiposMantenimiento">
                        <option value="Preventivo"></option>
                        <option value="Correctivo"></option>
                        <option value="Revisión de batería"></option>
                        <option value="Cambio de frenos"></option>
                        <option value="Revisión de motor"></option>
                        <option value="Llantas"></option>
                        <option value="Diagnóstico general"></option>
                    </datalist>
                    <?php if (isset($errors['tipo_manten'])): ?><div class="invalid-feedback"><?= e($errors['tipo_manten']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Trabajos realizados, repuestos usados, observaciones..."><?= old($item, 'descripcion') ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tecnico">Técnico responsable</label>
                    <input type="text" class="form-control <?= isset($errors['tecnico']) ? 'is-invalid' : '' ?>" id="tecnico" name="tecnico" value="<?= old($item, 'tecnico') ?>" placeholder="Nombre del técnico">
                    <?php if (isset($errors['tecnico'])): ?><div class="invalid-feedback"><?= e($errors['tecnico']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="costo">Costo (COP)</label>
                    <input type="number" step="0.01" min="0" class="form-control <?= isset($errors['costo']) ? 'is-invalid' : '' ?>" id="costo" name="costo" value="<?= old($item, 'costo', '0') ?>">
                    <?php if (isset($errors['costo'])): ?><div class="invalid-feedback"><?= e($errors['costo']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Fechas</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="fecha_inicio">Fecha de inicio</label>
                    <input type="datetime-local" class="form-control <?= isset($errors['fecha_inicio']) ? 'is-invalid' : '' ?>" id="fecha_inicio" name="fecha_inicio" value="<?= e($inicioLocal) ?>" required>
                    <?php if (isset($errors['fecha_inicio'])): ?><div class="invalid-feedback"><?= e($errors['fecha_inicio']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="fecha_fin">Fecha de fin <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="datetime-local" class="form-control <?= isset($errors['fecha_fin']) ? 'is-invalid' : '' ?>" id="fecha_fin" name="fecha_fin" value="<?= e($finLocal) ?>">
                    <?php if (isset($errors['fecha_fin'])): ?><div class="invalid-feedback"><?= e($errors['fecha_fin']) ?></div><?php endif; ?>
                    <div class="form-text">Déjala vacía si el mantenimiento sigue en proceso.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h2>Efectos en la flota</h2></div>
            <div class="card-body">
                <ul class="small text-muted ps-3 mb-3">
                    <li class="mb-2">Sin fecha de fin, la bicicleta pasa a estado <strong>mantenimiento</strong> y no podrá alquilarse.</li>
                    <li class="mb-2">Al finalizar, vuelve a <strong>disponible</strong> y se actualizan las fechas de mantenimiento.</li>
                    <li>El próximo mantenimiento se programa automáticamente 90 días después.</li>
                </ul>
                <div class="d-grid gap-2">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar mantenimiento' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= url('mantenimientos') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
