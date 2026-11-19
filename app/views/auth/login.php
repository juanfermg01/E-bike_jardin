<?php /** @var array $errors */ /** @var array $old */ ?>
<div class="auth-wrap">
    <aside class="auth-aside">
        <div class="hero-badge"><i class="bi bi-lightning-charge-fill"></i> Movilidad eléctrica</div>
        <h2>Pedalea menos.<br>Vive Jardín más.</h2>
        <p>Alquila una bicicleta eléctrica, recorre el pueblo y devuélvela en cualquiera de nuestras estaciones. Rápido, cómodo y sin emisiones.</p>
        <ul class="auth-points">
            <li><i class="bi bi-check-circle-fill"></i><span>Estaciones en los puntos más importantes de Jardín.</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span>Tarifa clara: $150 COP por minuto.</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span>Pago simulado y recibo digital al finalizar.</span></li>
            <li><i class="bi bi-check-circle-fill"></i><span>Panel administrativo con métricas en tiempo real.</span></li>
        </ul>
    </aside>

    <div class="auth-card">
        <h1>Iniciar sesión</h1>
        <p class="sub">Ingresa con tu correo y contraseña para gestionar tus alquileres.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 px-3 small rounded-3">
                <i class="bi bi-exclamation-triangle me-1"></i> Revisa los campos marcados.
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('login') ?>" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label" for="email">Correo electrónico</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email"
                           value="<?= old($old, 'email') ?>" placeholder="tu@correo.com" autocomplete="email" required>
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password"
                           placeholder="Tu contraseña" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" data-toggle-password="#password" aria-label="Mostrar contraseña">
                        <i class="bi bi-eye"></i>
                    </button>
                    <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                </div>
            </div>

            <button class="btn btn-brand w-100 py-2" type="submit">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
            </button>
        </form>

        <p class="text-center small text-muted mt-3 mb-0">
            ¿No tienes cuenta? <a href="<?= url('registro') ?>" class="fw-semibold">Regístrate aquí</a>
        </p>

        <div class="demo-box">
            <strong><i class="bi bi-info-circle me-1"></i>Cuentas de demostración</strong><br>
            Admin: <code>admin@ebikejardin.com</code> / <code>Admin123*</code><br>
            Operador: <code>operador@ebikejardin.com</code> / <code>Operador123*</code><br>
            Cliente: <code>cliente@ebikejardin.com</code> / <code>Cliente123*</code>
        </div>
    </div>
</div>
