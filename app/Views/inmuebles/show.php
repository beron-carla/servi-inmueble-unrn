<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= esc($inmueble['alias']) ?></h2>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Dirección</dt>
            <dd class="col-sm-9">
                <?= esc($inmueble['calle']) ?> <?= esc($inmueble['numero']) ?>
                <?php if ($inmueble['piso'] !== null) : ?>
                    , piso <?= esc($inmueble['piso']) ?>
                <?php endif ?>
                <?php if ($inmueble['depto'] !== null && $inmueble['depto'] !== '') : ?>
                    , depto. <?= esc($inmueble['depto']) ?>
                <?php endif ?>
            </dd>

            <dt class="col-sm-3">Ciudad</dt>
            <dd class="col-sm-9"><?= esc($inmueble['ciudad']) ?></dd>

            <dt class="col-sm-3">Tipo</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_tipo_inmueble($inmueble['tipo_nombre'])) ?></dd>

            <dt class="col-sm-3">Superficie</dt>
            <dd class="col-sm-9"><?= esc($inmueble['superficie']) ?> m²</dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_estado_propiedad($inmueble['estado'])) ?></dd>

            <?php if ($esAdmin) : ?>
                <dt class="col-sm-3">Propietario</dt>
                <dd class="col-sm-9"><?= esc($inmueble['propietario_nombre']) ?></dd>
            <?php endif ?>
        </dl>

        <h3 class="h5 mt-4">Servicios vinculados</h3>
        <?php if ($servicios === []) : ?>
            <p>No hay servicios vinculados.</p>
        <?php else : ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Servicio</th>
                            <th>Proveedor</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servicios as $servicio) : ?>
                            <tr>
                                <td><?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?></td>
                                <td><?= esc($servicio['proveedor_predeterminado']) ?></td>
                                <td><?= esc(etiqueta_estado_servicio($servicio['estado'])) ?></td>
                                <td>
                                    <a
                                        href="<?= route_to('servicios.show', $servicio['id_propiedad_servicio']) ?>"
                                        class="btn btn-sm btn-info"
                                    >Ver servicio</a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>

        <h3 class="h5 mt-4">Estado de facturación</h3>
        <div class="row">
            <div class="col-md-4">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= esc(formato_moneda((float) $resumenFacturacion['total_pendiente'])) ?></h3>
                        <p>Total Pendiente</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= esc(formato_moneda((float) $resumenFacturacion['total_pagado'])) ?></h3>
                        <p>Total Pagado</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= esc(formato_moneda((float) $resumenFacturacion['total_general'])) ?></h3>
                        <p>Total General</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <?php if ($inmueble['estado'] !== 'inactivo') : ?>
            <a
                href="<?= route_to('servicios.create', $inmueble['id_propiedad']) ?>"
                class="btn btn-success"
            >Vincular servicio</a>
        <?php endif ?>
        <a
            href="<?= route_to('inmuebles.edit', $inmueble['id_propiedad']) ?>"
            class="btn btn-primary"
        >Modificar</a>
        <?php if ($inmueble['estado'] === 'inactivo') : ?>
            <form
                action="<?= route_to('inmuebles.activate', $inmueble['id_propiedad']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">Reactivar</button>
            </form>
        <?php else : ?>
            <form
                action="<?= route_to('inmuebles.deactivate', $inmueble['id_propiedad']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Dar de baja</button>
            </form>
        <?php endif ?>
        <a
            href="<?= route_to($esAdmin ? 'admin.inmuebles.index' : 'inmuebles.index') ?>"
            class="btn btn-secondary"
        >Volver al listado</a>
    </div>
</div>
<?= $this->endSection() ?>
