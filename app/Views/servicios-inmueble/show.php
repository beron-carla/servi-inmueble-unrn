<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?> · <?= esc($servicio['proveedor_predeterminado']) ?>
        </h2>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-4">Inmueble</dt>
            <dd class="col-sm-8"><?= esc($servicio['propiedad_alias']) ?></dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8"><?= esc(etiqueta_estado_servicio($servicio['estado'])) ?></dd>

            <dt class="col-sm-4">CPE/Pagar</dt>
            <dd class="col-sm-8"><?= esc($servicio['codigo_pago_electronico']) ?></dd>

            <dt class="col-sm-4">Número de cuenta</dt>
            <dd class="col-sm-8"><?= esc($servicio['numero_cuenta_contrato']) ?></dd>

            <dt class="col-sm-4">Número de medidor</dt>
            <dd class="col-sm-8"><?= esc($servicio['numero_medidor'] ?: 'No corresponde') ?></dd>

            <dt class="col-sm-4">Domicilio de facturación</dt>
            <dd class="col-sm-8"><?= esc($servicio['domicilio_facturacion']) ?></dd>

            <dt class="col-sm-4">Titular</dt>
            <dd class="col-sm-8"><?= esc($servicio['titular']) ?></dd>
        </dl>
    </div>
    <div class="card-footer">
        <a href="<?= route_to('servicios.edit', $servicio['id_propiedad_servicio']) ?>" class="btn btn-primary">Modificar</a>

        <?php if ($servicio['estado'] === 'activo') : ?>
            <form
                action="<?= route_to('servicios.state', $servicio['id_propiedad_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="estado" value="pausado">
                <button type="submit" class="btn btn-warning">Pausar</button>
            </form>
        <?php elseif ($servicio['estado'] === 'pausado') : ?>
            <form
                action="<?= route_to('servicios.state', $servicio['id_propiedad_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="estado" value="en revision">
                <button type="submit" class="btn btn-info">Enviar a revisión</button>
            </form>
        <?php elseif ($servicio['estado'] === 'en revision') : ?>
            <form
                action="<?= route_to('servicios.state', $servicio['id_propiedad_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <input type="hidden" name="estado" value="activo">
                <button type="submit" class="btn btn-success">Activar</button>
            </form>
        <?php endif ?>

        <?php if (in_array($servicio['estado'], ['activo', 'pausado', 'en revision'], true)) : ?>
            <form
                action="<?= route_to('servicios.deactivate', $servicio['id_propiedad_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Dar de baja</button>
            </form>
        <?php endif ?>

        <a href="<?= route_to('inmuebles.show', $servicio['id_propiedad']) ?>" class="btn btn-secondary">Volver al inmueble</a>
    </div>
</div>
<?= $this->endSection() ?>
