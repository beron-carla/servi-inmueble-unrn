<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Datos del tipo de servicio</h2>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Nombre</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_tipo_servicio($tipo['tipo'])) ?></dd>

            <dt class="col-sm-3">Categoría</dt>
            <dd class="col-sm-9"><?= $tipo['categoria'] === 'publico' ? 'Público' : 'Privado' ?></dd>

            <dt class="col-sm-3">Unidad de medida</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_unidad($tipo['unidad_medida'])) ?></dd>

            <dt class="col-sm-3">Proveedor</dt>
            <dd class="col-sm-9"><?= esc($tipo['proveedor_predeterminado']) ?></dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><?= (int) $tipo['activo'] === 1 ? 'Activo' : 'Inactivo' ?></dd>
        </dl>
        <div class="alert alert-info mb-0" role="status">
            La cantidad de inmuebles vinculados se incorporará en la integración con HU-13.
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= route_to('admin.tipos.index') ?>" class="btn btn-secondary">Volver al catálogo</a>
        <a href="<?= route_to('admin.tipos.edit', $tipo['id_tipo_servicio']) ?>" class="btn btn-primary">Editar</a>
        <?php if ((int) $tipo['activo'] === 1) : ?>
            <form
                action="<?= route_to('admin.tipos.deactivate', $tipo['id_tipo_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning">Desactivar</button>
            </form>
        <?php else : ?>
            <form
                action="<?= route_to('admin.tipos.activate', $tipo['id_tipo_servicio']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">Reactivar</button>
            </form>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
