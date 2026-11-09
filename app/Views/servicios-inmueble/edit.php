<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title"><?= esc($servicio['propiedad_alias']) ?> · <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?></h2>
    </div>
    <div class="card-body">
        <?php $this->setData([
            'action' => route_to('servicios.update', $servicio['id_propiedad_servicio']),
            'boton' => 'Guardar cambios',
            'servicio' => $servicio,
            'tipos' => $tipos,
            'errores' => $errores,
            'anterior' => $anterior,
        ]) ?>
        <?= $this->include('servicios-inmueble/form') ?>
    </div>
    <div class="card-footer">
        <a href="<?= route_to('servicios.show', $servicio['id_propiedad_servicio']) ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</div>
<?= $this->endSection() ?>
