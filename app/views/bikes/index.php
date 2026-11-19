<?php
/** @var array $bikes */
/** @var array $stations */
/** @var array $pagination */
/** @var array $porEstado */
/** @var array $filters */
$isStaff = Auth::isStaff();
$isAdmin = Auth::isAdmin();
$totalBikes = array_sum(array_map('intval', $porEstado));
?>
<div class="page-head">
    <div>
        <h1>Bicicletas</h1>
        <p>Flota de E-Bike Jardín: disponibilidad, batería, estación y mantenimiento.</p>
    </div>
    <?php if ($isStaff): ?>
        <a href="<?= url('bicicletas/crear') ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i> Nueva bicicleta</a>
    <?php endif; ?>
</div>

<div class="d-flex gap-2 flex-wrap mb-3">
    <span class="chip"><i class="bi bi-bicycle"></i> <?= $totalBikes ?> en total</span>
    <span class="chip"><i class="bi bi-check2-circle"></i> <?= (int)($porEstado['disponible'] ?? 0) ?> disponibles</span>
    <span class="chip"><i class="bi bi-lightning-charge"></i> <?= (int)($porEstado['en_uso'] ?? 0) ?> en uso</span>
    <span class="chip"><i class="bi bi-tools"></i> <?= (int)($porEstado['mantenimiento'] ?? 0) ?> en mantenimiento</span>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="get" action="<?= url('bicicletas') ?>" class="filter-bar">
            <div class="input-group" style="min-width:240px">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" name="q" class="form-control" placeholder="Código, modelo o serie" value="<?= e($filters['q']) ?>">
            </div>
            <select name="estado" class="form-select">
                <option value="">Todos los estados</option>
                <?php foreach (['disponible' => 'Disponible', 'en_uso' => 'En uso', 'mantenimiento' => 'Mantenimiento', 'baja' => 'Baja'] as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['estado'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <select name="estacion" class="form-select">
                <option value="0">Todas las estaciones</option>
                <?php foreach ($stations as $s): ?>
                    <option value="<?= (int)$s['id_estacion'] ?>" <?= (int)$filters['estacion'] === (int)$s['id_estacion'] ? 'selected' : '' ?>><?= e($s['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-soft" type="submit"><i class="bi bi-funnel me-1"></i> Filtrar</button>
            <?php if ($filters['q'] || $filters['estado'] || $filters['estacion']): ?>
                <a class="btn btn-link text-muted" href="<?= url('bicicletas') ?>">Limpiar</a>
            <?php endif; ?>
        </form>
        <span class="text-muted small"><?= (int)$pagination['total'] ?> resultados</span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Bicicleta</th>
                    <th>Estación</th>
                    <th>Batería</th>
                    <th>Kilometraje</th>
                    <th>Próximo mantenimiento</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$bikes): ?>
                <tr><td colspan="7"><div class="empty-state"><i class="bi bi-bicycle"></i><h3>Sin bicicletas para mostrar</h3><p class="small mb-0">Ajusta los filtros o registra una nueva bicicleta.</p></div></td></tr>
            <?php else: foreach ($bikes as $b):
                $bat = (float)$b['nivel_bateria'];
                $barClass = $bat < 20 ? 'low' : ($bat < 50 ? 'medium' : '');
            ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="bike-icon" style="width:38px;height:38px;font-size:1.15rem"><i class="bi bi-bicycle"></i></span>
                            <div>
                                <div class="fw-semibold"><?= e($b['codigo_qr']) ?></div>
                                <small class="text-muted"><?= e($b['modelo']) ?> · <?= e($b['num_serie']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= e($b['estacion'] ?? 'Sin asignar') ?></td>
                    <td style="min-width:120px">
                        <div class="d-flex justify-content-between small mb-1"><span class="text-muted"><?= $bat ?>%</span></div>
                        <div class="battery-bar <?= $barClass ?>"><span style="width: <?= max(3, $bat) ?>%"></span></div>
                    </td>
                    <td><?= number_format((float)$b['kilometraje'], 1, ',', '.') ?> km</td>
                    <td><?= format_date($b['prox_manten_date'], 'd/m/Y') ?></td>
                    <td><?= badge_estado($b['estado']) ?></td>
                    <td>
                        <div class="actions">
                            <?php if ($isStaff): ?>
                                <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#qrModal"
                                        data-code="<?= e($b['codigo_qr']) ?>" data-model="<?= e($b['modelo']) ?>"
                                        title="Código QR"><i class="bi bi-qr-code"></i></button>
                                <a class="btn btn-sm btn-soft" href="<?= url('bicicletas/editar/' . $b['id_bicicleta']) ?>" title="Editar"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if ($b['estado'] === 'disponible'): ?>
                                <a class="btn btn-sm btn-outline-brand" href="<?= url('alquileres/iniciar?bici=' . $b['id_bicicleta']) ?>"><i class="bi bi-lightning-charge me-1"></i>Alquilar</a>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                                <form method="post" action="<?= url('bicicletas/' . $b['id_bicicleta'] . '/eliminar') ?>"
                                      data-confirm="Eliminar bicicleta" data-text="Se eliminará <?= e($b['codigo_qr']) ?>. Esta acción no se puede deshacer.">
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
        <?php render_pagination($pagination, 'bicicletas', $filters); ?>
    </div>
</div>

<?php if ($isStaff): ?>
<div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0" style="border-radius:20px">
            <div class="modal-header border-0">
                <h5 class="modal-title"><i class="bi bi-qr-code me-1"></i> Código QR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center">
                <div class="qr-box mx-auto" id="qrCanvas"></div>
                <h6 class="mt-3 mb-1" id="qrCodeLabel"></h6>
                <p class="text-muted small mb-0" id="qrModelLabel"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center">
                <button class="btn btn-brand btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('qrModal');
    if (!modal || typeof QRCode === 'undefined') return;
    modal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const code = button.getAttribute('data-code');
        const model = button.getAttribute('data-model');
        document.getElementById('qrCodeLabel').textContent = code;
        document.getElementById('qrModelLabel').textContent = model;
        const box = document.getElementById('qrCanvas');
        box.innerHTML = '';
        new QRCode(box, { text: code, width: 190, height: 190, correctLevel: QRCode.CorrectLevel.H });
    });
});
</script>
<?php endif; ?>
