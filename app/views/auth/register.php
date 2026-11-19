<?php /** @var array $errors */ /** @var array $old */ ?>
<div class="auth-wrap">
    <aside class="auth-aside">
        <div class="hero-badge"><i class="bi bi-bicycle"></i> Únete a la comunidad</div>
        <h2>Tu próxima vuelta<br>empieza aquí.</h2>
        <p>Crea tu cuenta de cliente para alquilar bicicletas eléctricas, consultar tu historial y administrar tus métodos de pago.</p>
        <ul class="auth-points">
            <li><i class="bi bi-check-circle-fill"></i><span>Registro gratuito y en menos de un minuto.</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span>Historial completo de recorridos y pagos.</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span>Disponible desde tu celular, tablet o computador.</span></li>
        </ul>
    </aside>

    <div class="auth-card">
        <h1>Crear cuenta</h1>
        <p class="sub">Completa tus datos para empezar a rodar.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3">
                <i class="bi bi-exclamation-triangle me-1"></i> Corrige los campos marcados e inténtalo de nuevo.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('registro') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre completo</label>
                <input type="text" class="form-control <?= isset($errors['nombre']) ? 'is-invalid' : '' ?>" id="nombre" name="nombre"
                       value="<?= old($old, 'nombre') ?>" placeholder="Ej: Camila Restrepo" required>
                <?php if (isset($errors['nombre'])): ?><div class="invalid-feedback"><?= e($errors['nombre']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email"
                       value="<?= old($old, 'email') ?>" placeholder="tu@correo.com" required>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label" for="telefono">Teléfono <span class="text-muted fw-normal">(opcional)</span></label>
                <input type="text" class="form-control <?= isset($errors['telefono']) ? 'is-invalid' : '' ?>" id="telefono" name="telefono"
                       value="<?= old($old, 'telefono') ?>" placeholder="300 123 4567">
                <?php if (isset($errors['telefono'])): ?><div class="invalid-feedback"><?= e($errors['telefono']) ?></div><?php endif; ?>
            </div>

            <div class="row g-2">
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="password">Contraseña</label>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                           placeholder="Mínimo 8 caracteres" required>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
                <div class="col-sm-6 mb-3">
                    <label class="form-label" for="password_confirm">Confirmar contraseña</label>
                    <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm"
                           placeholder="Repite la contraseña" required>
                    <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= e($errors['password_confirm']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="terminos" required>
                <label class="form-check-label small text-muted" for="terminos">
                    Acepto las condiciones del servicio y el tratamiento de mis datos.
                </label>
            </div>

            <button class="btn btn-brand w-100 py-2" type="submit">
                <i class="bi bi-person-plus me-1"></i> Crear mi cuenta
            </button>
        </form>

        <p class="text-center small text-muted mt-3 mb-0">
            ¿Ya tienes cuenta? <a href="<?= url('login') ?>" class="fw-semibold">Inicia sesión</a>
        </p>
    </div>
</div>
