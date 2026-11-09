<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">Catálogo de tipos de servicio</h2>
        <a href="<?= route_to('admin.tipos.create') ?>" class="btn btn-primary">
            <i class="fas fa-plus" aria-hidden="true"></i> Nuevo tipo de servicio
        </a>
    </div>
    <div class="card-body">
        <form action="<?= route_to('admin.tipos.index') ?>" method="get" class="mb-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="q">Buscar por nombre o proveedor</label>
                    <input type="search" class="form-control" id="q" name="q" value="<?= esc($filtros['q']) ?>">
                </div>
                <div class="form-group col-md-3">
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
                    <label for="activo">Estado</label>
                    <select class="form-control" id="activo" name="activo">
                        <option value="">Todos</option>
                        <option value="1" <?= $filtros['activo'] === '1' ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= $filtros['activo'] === '0' ? 'selected' : '' ?>>Inactivo</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= route_to('admin.tipos.index') ?>" class="btn btn-secondary">Limpiar</a>
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
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tipos as $tipo) : ?>
                        <tr>
                            <td><?= esc(etiqueta_tipo_servicio($tipo['tipo'])) ?></td>
                            <td><?= $tipo['categoria'] === 'publico' ? 'Público' : 'Privado' ?></td>
                            <td><?= esc(etiqueta_unidad($tipo['unidad_medida'])) ?></td>
                            <td><?= esc($tipo['proveedor_predeterminado']) ?></td>
                            <td><?= (int) $tipo['activo'] === 1 ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <a
                                    href="<?= route_to('admin.tipos.show', $tipo['id_tipo_servicio']) ?>"
                                    class="btn btn-sm btn-info"
                                >
                                    Ver detalle
                                </a>
                                <a
                                    href="<?= route_to('admin.tipos.edit', $tipo['id_tipo_servicio']) ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Editar
                                </a>
                                <?php if ((int) $tipo['activo'] === 1) : ?>
                                    <form
                                        action="<?= route_to('admin.tipos.deactivate', $tipo['id_tipo_servicio']) ?>"
                                        method="post"
                                        class="d-inline"
                                    >
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            Desactivar
                                        </button>
                                    </form>
                                <?php else : ?>
                                    <form
                                        action="<?= route_to('admin.tipos.activate', $tipo['id_tipo_servicio']) ?>"
                                        method="post"
                                        class="d-inline"
                                    >
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-success">
                                            Reactivar
                                        </button>
                                    </form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?= $pager?->links() ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
