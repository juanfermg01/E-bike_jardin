<?php
/** @var array $users */
/** @var array $roles */
/** @var array $totalPorRol */
/** @var array $porEstado */
/** @var array $pagination */
/** @var array $filters */
$yo = Auth::id();
?>
<div class="page-head">
    <div>
        <h1>Usuarios</h1>
        <p>Administra las cuentas, roles y estados de acceso al sistema.</p>
    </div>
    <a href="<?= url('usuarios/crear') ?>" class="btn btn-brand"><i class="bi bi-person-plus me-1"></i> Nuevo usuario</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-label">Usuarios totales</div>
            <div class="stat-value"><?= (int)$pagination['total'] ?></div>
            <div class="stat-hint">Cuentas registradas</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card lime">
            <div class="stat-icon"><i class="bi bi-person-check"></i></div>
            <div class="stat-label">Activos</div>
            <div class="stat-value"><?= (int)($porEstado['activo'] ?? 0) ?></div>
            <div class="stat-hint">Con acceso al sistema</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card warn">
            <div class="stat-icon"><i class="bi bi-person-slash"></i></div>
            <div class="stat-label">Inactivos</div>
            <div class="stat-value"><?= (int)($porEstado['inactivo'] ?? 0) ?></div>
            <div class="stat-hint">Sin acceso temporal</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card danger">
            <div class="stat-icon"><i class="bi bi-shield-exclamation"></i></div>
            <div class="stat-label">Suspendidos</div>
            <div class="stat-value"><?= (int)($porEstado['suspendido'] ?? 0) ?></div>
            <div class="stat-hint">Bloqueados por seguridad</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('usuarios') ?>" class="filter-bar">
            <div class="input-group" style="min-width:240px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Nombre, correo o teléfono" value="<?= e($filters['q']) ?>">
            </div>
            <select name="rol" class="form-select">
                <option value="0">Todos los roles</option>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= (int)$r['id_rol'] ?>" <?= (int)$filters['rol'] === (int)$r['id_rol'] ? 'selected' : '' ?>><?= e($r['nombre_rol']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach (['activo' => 'Activo', 'inactivo' => 'Inactivo', 'suspendido' => 'Suspendido'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['estado'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['q'] || $filters['rol'] || $filters['estado']): ?>
                <a class="btn btn-link text-muted" href="<?= url('usuarios') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> cuentas</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Teléfono</th>
                    <th>Alquileres</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i><h3>Sin usuarios para mostrar</h3><p class="small mb-0">Prueba con otros filtros o crea una cuenta nueva.</p></div></td></tr>
            <?php else: foreach ($users as $usr):
                $partes = preg_split('/\s+/', trim((string)$usr['nombre']));
                $iniciales = strtoupper(mb_substr($partes[0] ?? '', 0, 1) . mb_substr($partes[1] ?? '', 0, 1));
                $rolColor = ['ADMIN' => 'text-bg-danger', 'OPERADOR' => 'text-bg-info', 'CLIENTE' => 'text-bg-success'];
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm"><?= e($iniciales ?: 'US') ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($usr['nombre']) ?>
                                    <?php if ((int)$usr['id_usuario'] === $yo): ?><span class="chip ms-1 py-0">Tú</span><?php endif; ?>
                                </div>
                                <small class="text-muted"><?= e($usr['email']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge <?= $rolColor[$usr['codigo_rol'] ?? ''] ?? 'text-bg-secondary' ?> badge-soft"><?= e($usr['nombre_rol']) ?></span></td>
                    <td><?= e($usr['telefono'] ?: '—') ?></td>
                    <td><?= (int)$usr['total_alquileres'] ?></td>
                    <td><?= format_date($usr['fecha_registro'], 'd/m/Y') ?></td>
                    <td><?= badge_estado($usr['estado']) ?></td>
                    <td>
                        <div class="actions justify-content-end">
                            <a class="btn btn-sm btn-soft" href="<?= url('usuarios/editar/' . $usr['id_usuario']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <?php if ((int)$usr['id_usuario'] !== $yo): ?>
                                <form method="post" action="<?= url('usuarios/' . $usr['id_usuario'] . '/eliminar') ?>"
                                      data-confirm="Eliminar usuario" data-text="Se eliminará la cuenta de <?= e($usr['nombre']) ?>. Si tiene alquileres, el sistema no permitirá borrarla.">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Eliminar"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body py-2">
        <?php render_pagination($pagination, 'usuarios', $filters); ?>
    </div>
</div>

<div class="row g-3 mt-1">
    <?php foreach ($totalPorRol as $tr): ?>
        <div class="col-md-4">
            <div class="card"><div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small"><?= e($tr['nombre_rol']) ?></div>
                    <div class="stat-value" style="font-size:1.5rem"><?= (int)$tr['total'] ?></div>
                </div>
                <span class="stat-icon mb-0"><i class="bi bi-person-badge"></i></span>
            </div></div>
        </div>
    <?php endforeach; ?>
</div>
