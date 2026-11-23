<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Filtros de mis pagos</h2>
    </div>
    <form action="<?= route_to('pagos.index') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 form-group">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <?php foreach (['pendiente', 'validada', 'rechazada'] as $estado) : ?>
                            <option value="<?= esc($estado, 'attr') ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                                <?= esc(etiqueta_estado_pago($estado)) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label for="desde">Desde</label>
                    <input class="form-control" type="date" id="desde" name="desde" value="<?= esc($filtros['desde'], 'attr') ?>">
                </div>
                <div class="col-md-4 form-group">
                    <label for="hasta">Hasta</label>
                    <input class="form-control" type="date" id="hasta" name="hasta" value="<?= esc($filtros['hasta'], 'attr') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= route_to('pagos.index') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <?php if ($pagos === []) : ?>
            <p class="p-3 mb-0">No hay pagos registrados</p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Inmueble</th>
                        <th>Fecha</th>
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
                            <td><?= esc($pago['inmueble']) ?></td>
                            <td><?= esc(formato_fecha($pago['fecha_pago'])) ?></td>
                            <td><?= esc(formato_moneda((float) $pago['monto_abonado'])) ?></td>
                            <td><?= esc(ucfirst($pago['medio_pago'])) ?></td>
                            <td><?= esc(strtoupper((string) $pago['numero_comprobante'])) ?></td>
                            <td><?= esc(etiqueta_estado_pago($pago['estado'])) ?></td>
                            <td>
                                <?php if ($pago['comprobante_digital'] !== null) : ?>
                                    <a
                                        class="btn btn-sm btn-outline-primary"
                                        href="<?= route_to('pagos.comprobante', $pago['id_transaccion']) ?>"
                                        target="_blank"
                                    >Ver comprobante</a>
                                <?php else : ?>
                                    —
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
