<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Servicios disponibles</h2>
    </div>
    <div class="card-body">
        <form action="<?= route_to('catalogo.servicios') ?>" method="get" class="mb-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-5">
                    <label for="q">Buscar por nombre o proveedor</label>
                    <input type="search" class="form-control" id="q" name="q" value="<?= esc($filtros['q']) ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="categoria">Categoría</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Todas</option>
                        <option value="publico" <?= $filtros['categoria'] === 'publico' ? 'selected' : '' ?>>
                            Público
                        </option>
                        <option value="privado" <?= $filtros['categoria'] === 'privado' ? 'selected' : '' ?>>
                            Privado
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= route_to('catalogo.servicios') ?>" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive">
        <?php if ($tipos === []) : ?>
            <p class="mb-0">No hay tipos de servicio registrados</p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Categoría</th>
                        <th>Unidad de medida</th>
                        <th>Proveedor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo) : ?>
                        <tr>
                            <td><?= esc(etiqueta_tipo_servicio($tipo['tipo'])) ?></td>
                            <td><?= $tipo['categoria'] === 'publico' ? 'Público' : 'Privado' ?></td>
                            <td><?= esc(etiqueta_unidad($tipo['unidad_medida'])) ?></td>
                            <td><?= esc($tipo['proveedor_predeterminado']) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?= $pager?->links() ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
