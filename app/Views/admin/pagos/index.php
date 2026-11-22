<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Validación de pagos</h2>
    </div>
    <form action="<?= route_to('admin.pagos.index') ?>" method="get">
        <div class="card-body">
            <?= $this->include('partials/alerts') ?>
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente', 'validada', 'rechazada'] as $estado) : ?>
                            <option value="<?= esc($estado, 'attr') ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= esc(etiqueta_estado_pago($estado)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="propietario">Propietario</label>
                    <select class="form-control" id="propietario" name="propietario">
                        <option value="">Todos</option>
                        <?php foreach ($propietarios as $propietario) : ?>
                            <option value="<?= (int) $propietario['id_usuario'] ?>" <?= $filtros['propietario'] === (string) $propietario['id_usuario'] ? 'selected' : '' ?>><?= esc($propietario['nombre_completo']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="factura">Factura</label>
                    <input class="form-control" type="number" min="1" id="factura" name="factura" value="<?= esc($filtros['factura'], 'attr') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label for="medio_pago">Medio de pago</label>
                    <select class="form-control" id="medio_pago" name="medio_pago">
                        <option value="">Todos</option>
                        <?php foreach (['transferencia', 'debito automatico', 'efectivo', 'billetera virtual'] as $medio) : ?>
                            <option value="<?= esc($medio, 'attr') ?>" <?= $filtros['medio_pago'] === $medio ? 'selected' : '' ?>><?= esc(ucfirst($medio)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-1 form-group">
                    <label for="desde">Desde</label>
                    <input class="form-control" type="date" id="desde" name="desde" value="<?= esc($filtros['desde'], 'attr') ?>">
                </div>
                <div class="col-md-1 form-group">
                    <label for="hasta">Hasta</label>
                    <input class="form-control" type="date" id="hasta" name="hasta" value="<?= esc($filtros['hasta'], 'attr') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= route_to('admin.pagos.index') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <?php if ($pagos === []) : ?>
            <p class="p-3 mb-0"><?= esc($filtros['estado'] === 'pendiente' ? 'No hay pagos pendientes de validación' : 'No hay pagos registrados') ?></p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Propietario</th>
                        <th>Inmueble</th>
                        <th>Factura</th>
                        <th>Monto</th>
                        <th>Medio</th>
                        <th>Comprobante</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pago) : ?>
                        <tr>
                            <td><?= esc(formato_fecha($pago['fecha_pago'])) ?></td>
                            <td><?= esc($pago['propietario']) ?></td>
                            <td><?= esc($pago['inmueble']) ?></td>
                            <td>#<?= (int) $pago['id_factura'] ?></td>
                            <td><?= esc(formato_moneda((float) $pago['monto_abonado'])) ?></td>
                            <td><?= esc(ucfirst($pago['medio_pago'])) ?></td>
                            <td>
                                <?php if ($pago['comprobante_digital'] !== null) : ?>
                                    <a class="btn btn-sm btn-outline-primary" href="<?= route_to('pagos.comprobante', $pago['id_transaccion']) ?>" target="_blank">Ver</a>
                                <?php else : ?>
                                    —
                                <?php endif ?>
                            </td>
                            <td><?= esc(etiqueta_estado_pago($pago['estado'])) ?></td>
                            <td><a class="btn btn-sm btn-info" href="<?= route_to('admin.pagos.show', $pago['id_transaccion']) ?>">Ver detalle</a></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
