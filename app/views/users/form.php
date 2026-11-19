<?php
/** @var array $user */
/** @var array $roles */
/** @var array $errors */
/** @var string $action */
/** @var bool $isEdit */
$esPropio = $isEdit && (int)($user['id_usuario'] ?? 0) === Auth::id();
?>
<div class="page-head">
    <div>
        <h1><?= e($pageTitle) ?></h1>
        <p><?= $isEdit ? 'Actualiza los datos, el rol o la contraseña de la cuenta.' : 'Crea una cuenta y asigna su nivel de acceso.' ?></p>
    </div>
    <a href="<?= url('usuarios') ?>" class="btn btn-outline-brand"><i class="bi bi-arrow-left me-1"></i> Volver</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger rounded-3"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados en rojo.</div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="row g-3" novalidate>
    <?= csrf_field() ?>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h2>Datos personales</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-7">
                    <label class="form-label" for="nombre">Nombre completo</label>
                    <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre"
                           value="<?= old($user, 'nombre') ?>" placeholder="Ej: Camila Restrepo" required>
                    <?php if (isset($errors['nombre'])): ?><div class="invalid-feedback"><?= e($errors['nombre']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label class="form-label" for="telefono">Teléfono</label>
                    <input type="text" class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>" id="telefono" name="telefono"
                           value="<?= old($user, 'telefono') ?>" placeholder="300 123 4567">
                    <?php if (isset($errors['telefono'])): ?><div class="invalid-feedback"><?= e($errors['telefono']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="email">Correo electrónico</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email"
                           value="<?= old($user, 'email') ?>" placeholder="usuario@correo.com" required>
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    <div class="form-text">Se usa para iniciar sesión. Debe ser único en el sistema.</div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Contraseña</h2></div>
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="password"><?= $isEdit ? 'Nueva contraseña' : 'Contraseña' ?></label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                               placeholder="Mínimo 8 caracteres" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                        <button class="btn btn-soft" type="button" data-toggle-password="#password" title="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <?php if ($isEdit): ?><div class="form-text">Déjala vacía si no deseas cambiarla.</div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                    <div class="input-group">
                        <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm"
                               placeholder="Repite la contraseña" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password">
                        <button class="btn btn-soft" type="button" data-toggle-password="#password_confirm" title="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
                        <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h2>Rol y acceso</h2></div>
            <div class="card-body row g-3">
                <div class="col-12">
                    <label class="form-label" for="rol_id">Rol del usuario</label>
                    <select class="form-select <?= isset($errors['rol_id']) ? 'is-invalid' : '' ?>" id="rol_id" name="rol_id" <?= $esPropio ? 'disabled' : '' ?>>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= (int)$r['id_rol'] ?>" <?= (string)($user['rol_id'] ?? '') === (string)$r['id_rol'] ? 'selected' : '' ?>>
                                <?= e($r['nombre_rol']) ?> · nivel <?= (int)$r['nivel_acceso'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($esPropio): ?>
                        <input type="hidden" name="rol_id" value="<?= (int)$user['rol_id'] ?>">
                        <div class="form-text">No puedes cambiar tu propio rol.</div>
                    <?php endif; ?>
                    <?php if (isset($errors['rol_id'])): ?><div class="invalid-feedback"><?= e($errors['rol_id']) ?></div><?php endif; ?>
                </div>
                <div class="col-12">
                    <label class="form-label" for="estado">Estado de la cuenta</label>
                    <select class="form-select <?= isset($errors['estado']) ? 'is-invalid' : '' ?>" id="estado" name="estado" <?= $esPropio ? 'disabled' : '' ?>>
                        <?php foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($user['estado'] ?? 'activo') === $key ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($esPropio): ?>
                        <input type="hidden" name="estado" value="<?= e($user['estado']) ?>">
                        <div class="form-text">No puedes desactivar tu propia cuenta.</div>
                    <?php endif; ?>
                    <?php if (isset($errors['estado'])): ?><div class="invalid-feedback"><?= e($errors['estado']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <div class="note-box small mb-3">
                    <i class="bi bi-shield-lock me-1"></i> Las contraseñas se guardan cifradas con bcrypt. Nadie puede leerlas, ni siquiera el administrador.
                </div>
                <div class="d-grid gap-2">
                    <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> <?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
                    <a class="btn btn-outline-secondary" href="<?= url('usuarios') ?>">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
