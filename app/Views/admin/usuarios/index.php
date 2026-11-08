<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">Usuarios registrados</h2>
        <a href="<?= route_to('admin.usuarios.create') ?>" class="btn btn-primary">
            <i class="fas fa-user-plus" aria-hidden="true"></i> Nuevo usuario
        </a>
    </div>
    <div class="card-body">
        <form action="<?= route_to('admin.usuarios.index') ?>" method="get" class="mb-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-5">
                    <label for="q">Buscar por nombre o correo electrónico</label>
                    <input
                        type="search"
                        class="form-control"
                        id="q"
                        name="q"
                        value="<?= esc($filtros['q']) ?>"
                    >
                </div>
                <div class="form-group col-md-2">
                    <label for="rol">Rol</label>
                    <select class="form-control" id="rol" name="rol">
                        <option value="">Todos</option>
                        <option value="propietario" <?= $filtros['rol'] === 'propietario' ? 'selected' : '' ?>>
                            Propietario
                        </option>
                        <option value="admin" <?= $filtros['rol'] === 'admin' ? 'selected' : '' ?>>
                            Administrador
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Todos</option>
                        <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>
                            Activo
                        </option>
                        <option value="bloqueado" <?= $filtros['estado'] === 'bloqueado' ? 'selected' : '' ?>>
                            Bloqueado
                        </option>
                        <option value="baja" <?= $filtros['estado'] === 'baja' ? 'selected' : '' ?>>
                            Dado de baja
                        </option>
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="<?= route_to('admin.usuarios.index') ?>" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body table-responsive">
        <?php if ($usuarios === []) : ?>
            <p class="mb-0">No se encontraron usuarios.</p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover table-sm">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Fecha de alta</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $usuario) : ?>
                        <tr>
                            <td><?= esc($usuario['nombre_completo']) ?></td>
                            <td><?= esc($usuario['mail']) ?></td>
                            <td><?= esc(etiqueta_rol($usuario['rol'])) ?></td>
                            <td><?= esc(etiqueta_estado_usuario($usuario['estado'])) ?></td>
                            <td><?= esc(formato_fecha($usuario['fecha_alta'] ?? null)) ?></td>
                            <td>
                                <a
                                    href="<?= route_to('admin.usuarios.show', $usuario['id_usuario']) ?>"
                                    class="btn btn-sm btn-info"
                                >
                                    Ver detalle
                                </a>
                                <a
                                    href="<?= route_to('admin.usuarios.edit', $usuario['id_usuario']) ?>"
                                    class="btn btn-sm btn-primary"
                                >
                                    Modificar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
            <?= $pager?->links('usuarios') ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
