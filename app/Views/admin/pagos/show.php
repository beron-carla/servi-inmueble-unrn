<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Detalle del pago #<?= (int) $pago['id_transaccion'] ?></h2>
    </div>
    <div class="card-body">
        <?= $this->include('partials/alerts') ?>
        <dl class="row">
            <dt class="col-sm-3">Propietario</dt>
            <dd class="col-sm-9"><?= esc($pago['propietario']) ?></dd>
            <dt class="col-sm-3">Inmueble</dt>
            <dd class="col-sm-9"><?= esc($pago['inmueble']) ?></dd>
            <dt class="col-sm-3">Factura</dt>
            <dd class="col-sm-9">#<?= (int) $pago['id_factura'] ?></dd>
            <dt class="col-sm-3">Fecha de pago</dt>
            <dd class="col-sm-9"><?= esc(formato_fecha($pago['fecha_pago'])) ?></dd>
            <dt class="col-sm-3">Monto abonado</dt>
            <dd class="col-sm-9"><?= esc(formato_moneda((float) $pago['monto_abonado'])) ?></dd>
            <dt class="col-sm-3">Monto de factura</dt>
            <dd class="col-sm-9"><?= esc(formato_moneda((float) $pago['monto_total'])) ?></dd>
            <dt class="col-sm-3">Medio de pago</dt>
            <dd class="col-sm-9"><?= esc(ucfirst($pago['medio_pago'])) ?></dd>
            <dt class="col-sm-3">Número de comprobante</dt>
            <dd class="col-sm-9"><?= esc($pago['numero_comprobante']) ?></dd>
            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_estado_pago($pago['estado'])) ?></dd>
        </dl>

        <?php if ($pago['comprobante_digital'] !== null) : ?>
            <a
                class="btn btn-outline-primary"
                href="<?= route_to('pagos.comprobante', $pago['id_transaccion']) ?>"
                target="_blank"
            >Previsualizar / descargar comprobante</a>
        <?php else : ?>
            <p><strong>Comprobante:</strong> —</p>
        <?php endif ?>

        <?php if ($pago['estado'] === 'rechazada') : ?>
            <div class="mt-3 alert alert-warning">
                <strong>Motivo del rechazo:</strong> <?= esc($pago['motivo_rechazo'] ?? '') ?>
            </div>
        <?php endif ?>
    </div>

    <?php if ($pago['estado'] === 'pendiente') : ?>
        <div class="card-footer">
            <form action="<?= route_to('admin.pagos.validar', $pago['id_transaccion']) ?>" method="post" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">Validar pago</button>
            </form>

            <form action="<?= route_to('admin.pagos.rechazar', $pago['id_transaccion']) ?>" method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="motivo">Motivo del rechazo</label>
                    <textarea class="form-control" id="motivo" name="motivo" maxlength="255"><?= esc(old('motivo') ?? '') ?></textarea>
                    <?php if (isset($errores['motivo'])) : ?>
                        <small class="text-danger"><?= esc($errores['motivo']) ?></small>
                    <?php endif ?>
                </div>
                <button type="submit" class="btn btn-danger">Rechazar pago</button>
            </form>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
