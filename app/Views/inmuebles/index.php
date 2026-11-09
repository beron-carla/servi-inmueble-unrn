<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="card-title">Inmuebles registrados</h2>
        <a href="<?= route_to('inmuebles.create') ?>" class="btn btn-primary">
            <i class="fas fa-plus" aria-hidden="true"></i> Nuevo inmueble
        </a>
    </div>
    <div class="card-body">
        <form
            action="<?= route_to($esAdmin ? 'admin.inmuebles.index' : 'inmuebles.index') ?>"
            method="get"
            class="mb-3"
        >
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="ciudad">Ciudad</label>
                    <input
                        type="search"
                        class="form-control"
                        id="ciudad"
                        name="ciudad"
                        value="<?= esc($filtros['ciudad']) ?>"
                    >
                </div>
                <div class="form-group col-md-2">
                    <label for="tipo">Tipo</label>
                    <select class="form-control" id="tipo" name="tipo">
                        <option value="">Todos</option>
                        <?php foreach (['casa', 'departamento', 'local comercial', 'oficina', 'galpon'] as $tipo) : ?>
                            <option value="<?= esc($tipo) ?>" <?= $filtros['tipo'] === $tipo ? 'selected' : '' ?>>
                                <?= esc(etiqueta_tipo_inmueble($tipo)) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="estado">Estado</label>
                    <select class="form-control" id="estado" name="estado">
                        <option value="">Activos</option>
                        <?php foreach (['ocupado', 'disponible', 'en mantenimiento', 'inactivo'] as $estado) : ?>
                            <option value="<?= esc($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>>
                                <?= esc(etiqueta_estado_propiedad($estado)) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group col-md-<?= $esAdmin ? '2' : '3' ?>">
                    <label for="q">Buscar</label>
                    <input
                        type="search"
                        class="form-control"
                        id="q"
                        name="q"
                        value="<?= esc($filtros['q']) ?>"
                        placeholder="Alias, calle o ciudad"
                    >
                </div>
                <?php if ($esAdmin) : ?>
                    <div class="form-group col-md-3">
                        <label for="propietario">Propietario</label>
                        <select class="form-control" id="propietario" name="propietario">
                            <option value="">Todos</option>
                            <?php foreach ($propietarios as $propietario) : ?>
                                <option
                                    value="<?= esc($propietario['id_usuario']) ?>"
                                    <?= $filtros['propietario'] === (string) $propietario['id_usuario'] ? 'selected' : '' ?>
                                >
                                    <?= esc($propietario['nombre_completo']) ?><?= $propietario['estado'] === 'activo' ? '' : ' (Inactivo)' ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif ?>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a
                        href="<?= route_to($esAdmin ? 'admin.inmuebles.index' : 'inmuebles.index') ?>"
                        class="btn btn-secondary"
                    >Limpiar</a>
                </div>
            </div>
        </form>

        <?php if ($inmuebles === []) : ?>
            <p class="mb-0">No se encontraron inmuebles</p>
        <?php else : ?>
            <div class="row">
                <?php foreach ($inmuebles as $inmueble) : ?>
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-outline card-primary h-100">
                            <div class="card-header">
                                <h3 class="card-title"><?= esc($inmueble['alias']) ?></h3>
                            </div>
                            <div class="card-body">
                                <p class="mb-1">
                                    <strong>Dirección:</strong>
                                    <?= esc($inmueble['calle']) ?> <?= esc($inmueble['numero']) ?>
                                    <?php if ($inmueble['piso'] !== null) : ?>
                                        , piso <?= esc($inmueble['piso']) ?>
                                    <?php endif ?>
                                    <?php if ($inmueble['depto'] !== null && $inmueble['depto'] !== '') : ?>
                                        , depto. <?= esc($inmueble['depto']) ?>
                                    <?php endif ?>
                                </p>
                                <p class="mb-1"><strong>Ciudad:</strong> <?= esc($inmueble['ciudad']) ?></p>
                                <p class="mb-1">
                                    <strong>Tipo:</strong> <?= esc(etiqueta_tipo_inmueble($inmueble['tipo_nombre'])) ?>
                                </p>
                                <p class="mb-1"><strong>Superficie:</strong> <?= esc($inmueble['superficie']) ?> m²</p>
                                <p class="mb-1">
                                    <strong>Estado:</strong> <?= esc(etiqueta_estado_propiedad($inmueble['estado'])) ?>
                                </p>
                                <?php if ($esAdmin) : ?>
                                    <p class="mb-0">
                                        <strong>Propietario:</strong> <?= esc($inmueble['propietario_nombre']) ?>
                                    </p>
                                <?php endif ?>
                            </div>
                            <div class="card-footer">
                                <a
                                    href="<?= route_to('inmuebles.show', $inmueble['id_propiedad']) ?>"
                                    class="btn btn-sm btn-info"
                                >Ver detalle</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
            <?= $pager?->links() ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
