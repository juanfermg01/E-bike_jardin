<?php
/** @var array $item */
/** @var array $stations */
/** @var array $tipos */
/** @var array $errors */
/** @var string $action */
/** @var bool $isEdit */
$fechaLocal = !empty($item['fecha_registro']) && strtotime((string)$item['fecha_registro']) ? date('Y-m-d\TH:i', strtotime((string)$item['fecha_registro'])) : '';
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $isEdit ? 'Corrige los datos del registro seleccionado.' : 'Registra una compra, recarga o consumo eléctrico.' ?></p>
    </div>
    <a href="<?= url('energia') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h2>Datos del registro</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="id_estacion_fk">Estación</label>
                    <select class="form-select <?= isset($errors['id_estacion_fk']) ? 'is-invalid' : '' ?>" id="id_estacion_fk" name="id_estacion_fk" required>
                        <option value="">Selecciona una estación</option>
                        <?php foreach ($stations as $s): ?>
                            <option value="<?= (int)$s['id_estacion'] ?>" <?= (string)($item['id_estacion_fk'] ?? '') === (string)$s['id_estacion'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['id_estacion_fk'])): ?><div class="invalid-feedback"><?= e($errors['id_estacion_fk']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="tipo_operac">Tipo de operación</label>
                    <input type="text" class="form-control <?= isset($errors['tipo_operac']) ? 'is-invalid' : '' ?>" id="tipo_operac" name="tipo_operac"
                           value="<?= old($item, 'tipo_operac') ?>" list="tiposEnergia" placeholder="Ej: Recarga de baterías" required>
                    <datalist id="tiposEnergia">
                        <?php foreach ($tipos as $t): ?><option value="<?= e($t) ?>"></option><?php endforeach; ?>
                    </datalist>
                    <?php if (isset($errors['tipo_operac'])): ?><div class="invalid-feedback"><?= e($errors['tipo_operac']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="cantidad_kwh">Cantidad (kWh)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control <?= isset($errors['cantidad_kwh']) ? 'is-invalid' : '' ?>" id="cantidad_kwh" name="cantidad_kwh" value="<?= old($item, 'cantidad_kwh') ?>" required>
                    <?php if (isset($errors['cantidad_kwh'])): ?><div class="invalid-feedback"><?= e($errors['cantidad_kwh']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="costo_kwh">Costo por kWh (COP)</label>
                    <input type="number" step="0.0001" min="0.0001" class="form-control <?= isset($errors['costo_kwh']) ? 'is-invalid' : '' ?>" id="costo_kwh" name="costo_kwh" value="<?= old($item, 'costo_kwh') ?>" required>
                    <?php if (isset($errors['costo_kwh'])): ?><div class="invalid-feedback"><?= e($errors['costo_kwh']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="fecha_registro">Fecha y hora</label>
                    <input type="datetime-local" class="form-control <?= isset($errors['fecha_registro']) ? 'is-invalid' : '' ?>" id="fecha_registro" name="fecha_registro" value="<?= e($fechaLocal) ?>" required>
                    <?php if (isset($errors['fecha_registro'])): ?><div class="invalid-feedback"><?= e($errors['fecha_registro']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Detalle de la operación, proveedor, número de factura..."><?= old($item, 'descripcion') ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h2>Total calculado</h2></div>
            <div class="card-body">
                <div class="receipt-row"><span>Cantidad</span><strong id="previewKwh">0 kWh</strong></div>
                <div class="receipt-row"><span>Costo unitario</span><strong id="previewRate">$ 0 COP</strong></div>
                <div class="receipt-row receipt-total"><span>Total</span><strong class="text-success" id="previewTotal">$ 0 COP</strong></div>
                <div class="note-box small mt-3">
                    <i class="bi bi-database me-1"></i> El total se guarda como columna generada: MySQL lo calcula solo al insertar el registro.
                </div>
                <div class="d-grid gap-2 mt-3">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Registrar consumo' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= url('energia') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const kwh = document.getElementById('cantidad_kwh');
    const rate = document.getElementById('costo_kwh');
    const money = function (value) { return '$ ' + Math.round(value).toLocaleString('es-CO') + ' COP'; };
    function update() {
        const qty = parseFloat(kwh.value || '0');
        const price = parseFloat(rate.value || '0');
        document.getElementById('previewKwh').textContent = qty.toLocaleString('es-CO') + ' kWh';
        document.getElementById('previewRate').textContent = money(price);
        document.getElementById('previewTotal').textContent = money(qty * price);
    }
    if (kwh && rate) {
        kwh.addEventListener('input', update);
        rate.addEventListener('input', update);
        update();
    }
});
</script>
