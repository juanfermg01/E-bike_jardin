<?php
/** @var array $user */
/** @var array $stats */
/** @var array $metodos */
/** @var array $errors */
/** @var array $pwdErrors */
$partes = preg_split('/\s+/', trim((string)$user['nombre']));
$iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
$etiquetas = [
    'tarjeta_credito' => 'Tarjeta de crédito',
    'tarjeta_debito' => 'Tarjeta de débito',
    'transferencia' => 'Transferencia',
    'billetera_digital' => 'Billetera digital',
];
?>
<div class="page-head">
    <div>
        <h1>Mi perfil</h1>
        <p>Actualiza tus datos personales y la seguridad de tu cuenta.</p>
    </div>
    <a href="<?= url('dashboard') ?>" class="btn btn-outline-brand"><i class="bi bi-speedometer2 me-1"></i> Ir al panel</a>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                <span class="avatar avatar-lg mx-auto mb-3"><?= e($iniciales ?: 'EB') ?></span>
                <h2 class="h5 mb-1"><?= e($user['nombre']) ?></h2>
                <div class="text-muted small mb-2"><?= e($user['email']) ?></div>
                <span class="badge text-bg-success badge-soft mb-3"><?= e($user['nombre_rol']) ?></span>

                <div class="row g-2 text-start">
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['alquileres'] ?></strong><span>Alquileres</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['completados'] ?></strong><span>Completados</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= format_money($stats['gastado']) ?></strong><span>Pagado</span></div></div>
                    <div class="col-6"><div class="stat-mini"><strong><?= (int)$stats['metodos'] ?></strong><span>Métodos</span></div></div>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Métodos de pago</h2><a class="btn btn-sm btn-soft" href="<?= url('pagos') ?>">Gestionar</a></div>
            <div class="card-body p-0">
                <?php if (!$metodos): ?>
                    <div class="empty-state py-4"><i class="bi bi-credit-card"></i><p class="small mb-0">Sin métodos activos.</p></div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($metodos as $m): ?>
                            <li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <div>
                                    <div class="small fw-semibold"><?= e($etiquetas[$m['tipo']] ?? $m['tipo']) ?></div>
                                    <small class="text-muted"><?= e($m['alias'] ?: 'Sin alias') ?> · •••• <?= e($m['ultimos_digitos'] ?: '0000') ?></small>
                                </div>
                                <i class="bi bi-credit-card text-muted"></i>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Cuenta</h2></div>
            <div class="card-body small">
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">UUID</span><span class="text-truncate ms-2" style="max-width:65%"><?= e($user['uuid']) ?></span></div>
                <div class="d-flex justify-content-between py-1 border-bottom"><span class="text-muted">Estado</span><?= badge_estado($user['estado']) ?></div>
                <div class="d-flex justify-content-between py-1"><span class="text-muted">Miembro desde</span><strong><?= format_date($user['fecha_registro'], 'd/m/Y') ?></strong></div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h2>Datos personales</h2></div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger rounded-3 small"><i class="bi bi-exclamation-triangle me-1"></i> Revisa los datos marcados.</div>
                <?php endif; ?>
                <form method="post" action="<?= url('perfil/actualizar') ?>" class="row g-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="col-md-7">
                        <label class="form-label" for="nombre">Nombre completo</label>
                        <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre" value="<?= e($user['nombre']) ?>" required>
                        <?php if (isset($errors['nombre'])): ?><div class="invalid-feedback"><?= e($errors['nombre']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label" for="telefono">Teléfono</label>
                        <input type="text" class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>" id="telefono" name="telefono" value="<?= e($user['telefono']) ?>" placeholder="300 123 4567">
                        <?php if (isset($errors['telefono'])): ?><div class="invalid-feedback"><?= e($errors['telefono']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($user['email']) ?>" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-brand" type="submit"><i class="bi bi-save me-1"></i> Guardar datos</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><h2>Cambiar contraseña</h2></div>
            <div class="card-body">
                <?php if (!empty($pwdErrors)): ?>
                    <div class="alert alert-danger rounded-3 small"><i class="bi bi-shield-exclamation me-1"></i> Verifica los campos de la contraseña.</div>
                <?php endif; ?>
                <form method="post" action="<?= url('perfil/password') ?>" class="row g-3" novalidate>
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" for="current_password">Contraseña actual</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?= isset($pwdErrors['current_password']) ? 'is-invalid' : '' ?>" id="current_password" name="current_password" required autocomplete="current-password">
                            <button class="btn btn-soft" type="button" data-toggle-password="#current_password" title="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
                            <?php if (isset($pwdErrors['current_password'])): ?><div class="invalid-feedback"><?= e($pwdErrors['current_password']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Nueva contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?= isset($pwdErrors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Mínimo 8 caracteres" required autocomplete="new-password">
                            <button class="btn btn-soft" type="button" data-toggle-password="#password" title="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
                            <?php if (isset($pwdErrors['password'])): ?><div class="invalid-feedback"><?= e($pwdErrors['password']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirm">Confirmar nueva contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?= isset($pwdErrors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" required autocomplete="new-password">
                            <button class="btn btn-soft" type="button" data-toggle-password="#password_confirm" title="Mostrar u ocultar"><i class="bi bi-eye"></i></button>
                            <?php if (isset($pwdErrors['password_confirm'])): ?><div class="invalid-feedback"><?= e($pwdErrors['password_confirm']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="note-box small py-2 mb-0"><i class="bi bi-shield-lock me-1"></i> Se almacena con bcrypt (costo 12).</div>
                        <button class="btn btn-brand" type="submit"><i class="bi bi-key me-1"></i> Actualizar contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
