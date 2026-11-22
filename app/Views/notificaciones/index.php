<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Filtrar notificaciones</h2>
    </div>
    <form action="<?= route_to('notificaciones.index') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="tipo">Tipo</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach ([
                            'por_vencer' => 'Por vencer',
                            'vencida' => 'Vencida',
                            'pago_validado' => 'Pago validado',
                            'pago_rechazado' => 'Pago rechazado',
                        ] as $valor => $etiqueta) : ?>
                            <option value="<?= esc($valor, 'attr') ?>" <?= $filtros['tipo'] === $valor ? 'selected' : '' ?>>
                                <?= esc($etiqueta) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="leida">Estado de lectura</label>
                    <select class="form-control" id="leida" name="leida">
                        <option value="">Todas</option>
                        <option value="0" <?= $filtros['leida'] === '0' ? 'selected' : '' ?>>No leídas</option>
                        <option value="1" <?= $filtros['leida'] === '1' ? 'selected' : '' ?>>Leídas</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
            <a href="<?= route_to('notificaciones.index') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title mb-0">Listado</h2>
        <?php if ((string) session('rol') === 'propietario') : ?>
            <form action="<?= route_to('notificaciones.leer-todas') ?>" method="post" class="mb-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    Marcar todas como leídas
                </button>
            </form>
        <?php endif ?>
    </div>
    <div class="card-body p-0">
        <?php if ($notificaciones === []) : ?>
            <p class="p-3 mb-0">No tenés notificaciones</p>
        <?php else : ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notificaciones as $notificacion) : ?>
                    <?php
                    $icono = match ($notificacion['tipo']) {
                        'por_vencer' => 'fa-clock text-warning',
                        'vencida' => 'fa-exclamation-circle text-danger',
                        'pago_validado' => 'fa-check-circle text-success',
                        'pago_rechazado' => 'fa-times-circle text-danger',
                        default => 'fa-bell',
                    };
                    ?>
                    <div class="list-group-item <?= (int) $notificacion['leida'] === 0 ? 'font-weight-bold' : '' ?>">
                        <div class="d-flex justify-content-between">
                            <div>
                                <i class="fas <?= esc($icono, 'attr') ?> mr-2"></i>
                                <?= esc($notificacion['mensaje']) ?>
                                <?php if ($notificacion['tipo'] === 'pago_rechazado' && ! empty($notificacion['motivo_rechazo'])) : ?>
                                    <br><small>Motivo: <?= esc($notificacion['motivo_rechazo']) ?></small>
                                <?php elseif ($notificacion['tipo'] === 'pago_validado' || $notificacion['tipo'] === 'pago_rechazado') : ?>
                                    <br><small>Estado del pago:
                                        <?= esc(etiqueta_estado_pago((string) ($notificacion['estado_pago'] ?? 'pendiente'))) ?>
                                    </small>
                                <?php endif ?>
                                <br>
                                <small class="text-muted">
                                    <?= esc(formato_fecha(substr((string) $notificacion['fecha_creacion'], 0, 10))) ?>
                                    <?= esc(substr((string) $notificacion['fecha_creacion'], 11, 5)) ?>
                                </small>
                                <a
                                    class="btn btn-link btn-sm"
                                    href="<?= esc(route_to('facturas.index') . '?id_factura=' . (int) $notificacion['id_factura'], 'attr') ?>"
                                >
                                    Ver factura
                                </a>
                            </div>
                            <?php if ((string) session('rol') === 'propietario' && (int) $notificacion['leida'] === 0) : ?>
                                <form action="<?= route_to('notificaciones.leer', (int) $notificacion['id_notificacion']) ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Marcar leída</button>
                                </form>
                            <?php endif ?>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endif ?>
    </div>
    <?php if ($paginacion['paginas'] > 1) : ?>
        <div class="card-footer">
            <nav aria-label="Paginación de notificaciones">
                <ul class="pagination pagination-sm mb-0">
                    <?php foreach ([
                        'Anterior' => max(1, $paginacion['pagina'] - 1),
                        'Siguiente' => min($paginacion['paginas'], $paginacion['pagina'] + 1),
                    ] as $etiqueta => $pagina) : ?>
                        <?php $deshabilitado = ($etiqueta === 'Anterior' && $paginacion['pagina'] === 1)
                            || ($etiqueta === 'Siguiente' && $paginacion['pagina'] === $paginacion['paginas']); ?>
                        <li class="page-item<?= $deshabilitado ? ' disabled' : '' ?>">
                            <a
                                class="page-link"
                                href="<?= esc(route_to('notificaciones.index') . '?' . http_build_query(array_filter($filtros) + ['page' => $pagina]), 'attr') ?>"
                            >
                                <?= esc($etiqueta) ?>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </nav>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
