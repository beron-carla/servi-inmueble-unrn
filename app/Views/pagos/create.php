<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Datos del pago</h2>
    </div>
    <form action="<?= route_to('pagos.store', $factura['id_factura']) ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Inmueble</dt>
                <dd class="col-sm-9"><?= esc($factura['inmueble']) ?></dd>
                <dt class="col-sm-3">Monto total</dt>
                <dd class="col-sm-9"><?= esc(formato_moneda((float) $factura['monto_total'])) ?></dd>
                <dt class="col-sm-3">Estado</dt>
                <dd class="col-sm-9"><?= esc(ucfirst($estadoCalculado)) ?></dd>
            </dl>

            <div class="form-group">
                <label for="monto_abonado">Monto abonado</label>
                <input
                    class="form-control"
                    type="number"
                    step="0.01"
                    id="monto_abonado"
                    name="monto_abonado"
                    value="<?= esc($anterior['monto_abonado'] ?? $factura['monto_total'], 'attr') ?>"
                    required
                >
                <?php if (isset($errores['monto_abonado'])) : ?>
                    <small class="text-danger"><?= esc($errores['monto_abonado']) ?></small>
                <?php endif ?>
            </div>
            <div class="form-group">
                <label for="fecha_pago">Fecha de pago</label>
                <input
                    class="form-control"
                    type="date"
                    id="fecha_pago"
                    name="fecha_pago"
                    value="<?= esc($anterior['fecha_pago'] ?? date('Y-m-d'), 'attr') ?>"
                    required
                >
                <?php if (isset($errores['fecha_pago'])) : ?>
                    <small class="text-danger"><?= esc($errores['fecha_pago']) ?></small>
                <?php endif ?>
            </div>
            <div class="form-group">
                <label for="medio_pago">Medio de pago</label>
                <?php $medioSeleccionado = $anterior['medio_pago'] ?? ''; ?>
                <select class="form-control" id="medio_pago" name="medio_pago" required>
                    <option value="">Seleccione</option>
                    <option value="transferencia" <?= $medioSeleccionado === 'transferencia' ? 'selected' : '' ?>>Transferencia</option>
                    <option value="debito automatico" <?= $medioSeleccionado === 'debito automatico' ? 'selected' : '' ?>>Débito automático</option>
                    <option value="efectivo" <?= $medioSeleccionado === 'efectivo' ? 'selected' : '' ?>>Efectivo</option>
                    <option value="billetera virtual" <?= $medioSeleccionado === 'billetera virtual' ? 'selected' : '' ?>>Billetera virtual</option>
                </select>
                <?php if (isset($errores['medio_pago'])) : ?>
                    <small class="text-danger"><?= esc($errores['medio_pago']) ?></small>
                <?php endif ?>
            </div>
            <?php if (isset($errores['registro'])) : ?>
                <div class="alert alert-danger"><?= esc($errores['registro']) ?></div>
            <?php endif ?>
            <div class="form-group">
                <p class="form-text text-muted">
                    El número de comprobante se generará automáticamente al registrar el pago
                    (formato CP-YYYYMMDD-NNNNNN).
                </p>
            </div>
            <div class="form-group">
                <label for="comprobante">Comprobante digital</label>
                <input class="form-control-file" type="file" id="comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,application/pdf,image/jpeg">
                <small class="form-text text-muted">PDF, JPG o JPEG. Máximo 5 MB.</small>
                <?php if (isset($errores['comprobante'])) : ?>
                    <small class="text-danger d-block"><?= esc($errores['comprobante']) ?></small>
                <?php endif ?>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Registrar pago</button>
            <a href="<?= route_to('facturas.index') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
