<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Vincular servicio a <?= esc($inmueble['alias']) ?></h2>
    </div>
    <div class="card-body">
        <?php if ($tipos === []) : ?>
            <div class="alert alert-warning" role="alert">
                No hay tipos de servicio disponibles. Contacte al administrador.
            </div>
        <?php else : ?>
            <?php $this->setData([
                'action' => route_to('servicios.store', $inmueble['id_propiedad']),
                'boton' => 'Vincular servicio',
                'servicio' => [],
                'tipos' => $tipos,
                'errores' => $errores,
                'anterior' => $anterior,
            ]) ?>
            <?= $this->include('servicios-inmueble/form') ?>
        <?php endif ?>
    </div>
    <div class="card-footer">
        <a href="<?= route_to('inmuebles.show', $inmueble['id_propiedad']) ?>" class="btn btn-secondary">Volver al inmueble</a>
    </div>
</div>
<?= $this->endSection() ?>
