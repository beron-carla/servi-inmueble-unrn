<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$transiciones = [
    'activo' => ['pausado'],
    'pausado' => ['en revision'],
    'en revision' => ['activo'],
];
$siguientesEstados = $transiciones[$servicio['estado']] ?? [];
?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?>
            — <?= esc($servicio['proveedor_predeterminado']) ?>
        </h2>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-4">Inmueble</dt>
            <dd class="col-sm-8">
                <a href="<?= route_to('inmuebles.show', $servicio['id_propiedad']) ?>">
                    <?= esc($servicio['alias_propiedad']) ?>
                </a>
            </dd>

            <dt class="col-sm-4">Estado</dt>
            <dd class="col-sm-8"><?= esc(etiqueta_estado_servicio($servicio['estado'])) ?></dd>

            <dt class="col-sm-4">Categoría</dt>
            <dd class="col-sm-8">
                <?= esc(['publico' => 'Público', 'privado' => 'Privado'][$servicio['categoria']] ?? $servicio['categoria']) ?>
            </dd>

            <dt class="col-sm-4">Unidad de medida</dt>
            <dd class="col-sm-8"><?= esc(etiqueta_unidad($servicio['unidad_medida'])) ?></dd>

            <dt class="col-sm-4">CPE/Pagar</dt>
            <dd class="col-sm-8"><?= esc($servicio['codigo_pago_electronico']) ?></dd>

            <dt class="col-sm-4">Número de cuenta</dt>
            <dd class="col-sm-8"><?= esc($servicio['numero_cuenta_contrato']) ?></dd>

            <dt class="col-sm-4">Número de medidor</dt>
            <dd class="col-sm-8"><?= esc($servicio['numero_medidor'] ?: 'No informado') ?></dd>

            <dt class="col-sm-4">Domicilio de facturación</dt>
            <dd class="col-sm-8"><?= esc($servicio['domicilio_facturacion']) ?></dd>

            <dt class="col-sm-4">Titular</dt>
            <dd class="col-sm-8"><?= esc($servicio['titular']) ?></dd>
        </dl>

        <div class="alert <?= $servicio['estado'] === 'activo' ? 'alert-success' : 'alert-secondary' ?>" role="status">
            <?= $servicio['estado'] === 'activo'
                ? 'Este servicio admite carga de facturas.'
                : 'Este servicio no admite carga de facturas mientras no esté Activo.' ?>
        </div>
    </div>
    <div class="card-footer">
        <?php if ($servicio['estado'] === 'activo' && $servicio['estado_propiedad'] !== 'inactivo') : ?>
            <a
                href="<?= route_to('facturas.create', $servicio['id_propiedad_servicio']) ?>"
                class="btn btn-success"
            >Registrar factura</a>
        <?php endif ?>
        <?php if ($servicio['estado'] !== 'inactivo') : ?>
            <a
                href="<?= route_to('servicios.edit', $servicio['id_propiedad_servicio']) ?>"
                class="btn btn-primary"
            >Modificar datos</a>

            <?php foreach ($siguientesEstados as $estado) : ?>
                <form
                    action="<?= route_to('servicios.state', $servicio['id_propiedad_servicio']) ?>"
                    method="post"
                    class="d-inline"
                >
                    <?= csrf_field() ?>
                    <input type="hidden" name="estado" value="<?= esc($estado, 'attr') ?>">
                    <button type="submit" class="btn btn-warning">
                        Pasar a <?= esc(etiqueta_estado_servicio($estado)) ?>
                    </button>
                </form>
            <?php endforeach ?>

            <form
                action="<?= route_to('servicios.deactivate', $servicio['id_propiedad_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Dar de baja</button>
            </form>
        <?php endif ?>
        <a
            href="<?= route_to('inmuebles.show', $servicio['id_propiedad']) ?>"
            class="btn btn-secondary"
        >Volver al inmueble</a>
    </div>
</div>
<?= $this->endSection() ?>
