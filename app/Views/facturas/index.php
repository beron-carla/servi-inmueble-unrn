<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Filtrar facturas</h2>
    </div>
    <form action="<?= route_to('facturas.index') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="inmueble">Inmueble</label>
                    <select class="form-control" id="inmueble" name="inmueble">
                        <option value="">Todos los inmuebles</option>
                        <?php foreach ($propiedades as $propiedad) : ?>
                            <option
                                value="<?= esc((string) $propiedad['id_propiedad'], 'attr') ?>"
                                <?= $filtros['inmueble'] === (string) $propiedad['id_propiedad'] ? 'selected' : '' ?>
                            >
                                <?= esc($propiedad['alias']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="servicio">Servicio</label>
                    <select class="form-control" id="servicio" name="servicio">
                        <option value="">Todos los servicios</option>
                        <?php foreach ($servicios as $servicio) : ?>
                            <option
                                value="<?= esc((string) $servicio['id_propiedad_servicio'], 'attr') ?>"
                                <?= $filtros['servicio'] === (string) $servicio['id_propiedad_servicio'] ? 'selected' : '' ?>
                            >
                                <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?> —
                                <?= esc($servicio['proveedor']) ?> (<?= esc($servicio['alias']) ?>)
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= $filtros['estado'] === 'pendiente' ? 'selected' : '' ?>>
                            Pendiente
                        </option>
                        <option value="pagada" <?= $filtros['estado'] === 'pagada' ? 'selected' : '' ?>>
                            Pagada
                        </option>
                        <option value="vencida" <?= $filtros['estado'] === 'vencida' ? 'selected' : '' ?>>
                            Vencida
                        </option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="desde">Vencimiento desde</label>
                    <input
                        class="form-control"
                        type="date"
                        id="desde"
                        name="desde"
                        value="<?= esc($filtros['desde'], 'attr') ?>"
                    >
                </div>
                <div class="col-md-4 form-group">
                    <label for="hasta">Vencimiento hasta</label>
                    <input
                        class="form-control"
                        type="date"
                        id="hasta"
                        name="hasta"
                        value="<?= esc($filtros['hasta'], 'attr') ?>"
                    >
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            <a href="<?= route_to('facturas.index') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3><?= esc(formato_moneda((float) $resumen['total_pendiente'])) ?></h3>
                <p>Total Pendiente</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-success">
            <div class="inner">
                <h3><?= esc(formato_moneda((float) $resumen['total_pagado'])) ?></h3>
                <p>Total Pagado</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box bg-info">
            <div class="inner">
                <h3><?= esc(formato_moneda((float) $resumen['total_general'])) ?></h3>
                <p>Total General</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Listado de facturas</h2>
    </div>
    <div class="card-body table-responsive p-0">
        <?php if ($facturas === []) : ?>
            <p class="p-3 mb-0">No hay facturas registradas</p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover table-sm mb-0">
                <thead>
                    <tr>
                        <th>Inmueble</th>
                        <th>Servicio</th>
                        <th>Período</th>
                        <th>Vencimiento aplicable</th>
                        <th class="text-right">Monto</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura) : ?>
                        <?php
                        $periodo = $factura['periodo'] === 'anio'
                            ? (string) $factura['periodo_anio']
                            : sprintf('%02d/%d', (int) $factura['periodo_mes'], (int) $factura['periodo_anio']);
                        $badge = match ($factura['estado_calculado']) {
                            'pagada' => 'success',
                            'vencida' => 'danger',
                            default => 'warning',
                        };
                        ?>
                        <tr>
                            <td>
                                <?= esc($factura['inmueble']) ?>
                                <br>
                                <small class="text-muted">
                                    <?= esc(trim($factura['calle'] . ' ' . $factura['numero'])) ?>
                                </small>
                            </td>
                            <td>
                                <?= esc(etiqueta_tipo_servicio($factura['tipo_servicio'])) ?>
                                <br>
                                <small class="text-muted"><?= esc($factura['proveedor']) ?></small>
                            </td>
                            <td><?= esc($periodo) ?></td>
                            <td><?= esc(formato_fecha($factura['vencimiento_aplicable'])) ?></td>
                            <td class="text-right"><?= esc(formato_moneda((float) $factura['monto_total'])) ?></td>
                            <td>
                                <span class="badge badge-<?= esc($badge, 'attr') ?>">
                                    <?= esc(ucfirst($factura['estado_calculado'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
    <?php if ($paginacion['paginas'] > 1) : ?>
        <?php
        $parametros = array_filter($filtros, static fn (string $valor): bool => $valor !== '');
        $primeraFila = (($paginacion['pagina'] - 1) * $paginacion['por_pagina']) + 1;
        $ultimaFila = min($primeraFila + $paginacion['por_pagina'] - 1, $paginacion['total']);
        ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <span>Mostrando <?= esc((string) $primeraFila) ?>–<?= esc((string) $ultimaFila) ?>
                de <?= esc((string) $paginacion['total']) ?> facturas</span>
            <nav aria-label="Paginación de facturas">
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
                                href="<?= esc(route_to('facturas.index') . '?' . http_build_query($parametros + ['page' => $pagina]), 'attr') ?>"
                                aria-label="<?= esc($etiqueta, 'attr') ?>"
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
