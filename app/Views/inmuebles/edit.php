<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Modificar inmueble</h2>
    </div>
    <form action="<?= route_to('inmuebles.update', $inmueble['id_propiedad']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <?php if ($inmueble['estado'] === 'inactivo') : ?>
                <div class="alert alert-warning" role="status">
                    El inmueble está inactivo. Para cambiar su estado, debe reactivarlo.
                </div>
            <?php endif ?>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="alias">Alias</label>
                    <input
                        type="text"
                        class="form-control<?= isset($errores['alias']) ? ' is-invalid' : '' ?>"
                        id="alias"
                        name="alias"
                        value="<?= esc($anterior['alias'] ?? $inmueble['alias']) ?>"
                        maxlength="45"
                        required
                    >
                    <?php if (isset($errores['alias'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['alias']) ?></div>
                    <?php endif ?>
                </div>
                <div class="form-group col-md-6">
                    <label for="id_tipo_inmueble">Tipo</label>
                    <select
                        class="form-control<?= isset($errores['id_tipo_inmueble']) ? ' is-invalid' : '' ?>"
                        id="id_tipo_inmueble"
                        name="id_tipo_inmueble"
                        required
                    >
                        <option value="">Seleccione un tipo</option>
                        <?php foreach ($opciones['tipos'] as $tipo) : ?>
                            <?php $idTipo = (string) $tipo['id_tipo_inmueble'] ?>
                            <option
                                value="<?= esc($idTipo) ?>"
                                <?= ($anterior['id_tipo_inmueble'] ?? (string) $inmueble['id_tipo_inmueble']) === $idTipo ? 'selected' : '' ?>
                            >
                                <?= esc(etiqueta_tipo_inmueble($tipo['nombre'])) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                    <?php if (isset($errores['id_tipo_inmueble'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['id_tipo_inmueble']) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-6">
                    <label for="calle">Calle</label>
                    <input
                        type="text"
                        class="form-control<?= isset($errores['calle']) ? ' is-invalid' : '' ?>"
                        id="calle"
                        name="calle"
                        value="<?= esc($anterior['calle'] ?? $inmueble['calle']) ?>"
                        maxlength="45"
                        required
                    >
                    <?php if (isset($errores['calle'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['calle']) ?></div>
                    <?php endif ?>
                </div>
                <div class="form-group col-md-3">
                    <label for="numero">Número</label>
                    <input
                        type="number"
                        class="form-control<?= isset($errores['numero']) ? ' is-invalid' : '' ?>"
                        id="numero"
                        name="numero"
                        value="<?= esc($anterior['numero'] ?? $inmueble['numero']) ?>"
                        step="1"
                        required
                    >
                    <?php if (isset($errores['numero'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['numero']) ?></div>
                    <?php endif ?>
                </div>
                <div class="form-group col-md-3">
                    <label for="piso">Piso (opcional)</label>
                    <input
                        type="number"
                        class="form-control<?= isset($errores['piso']) ? ' is-invalid' : '' ?>"
                        id="piso"
                        name="piso"
                        value="<?= esc($anterior['piso'] ?? $inmueble['piso'] ?? '') ?>"
                        step="1"
                    >
                    <?php if (isset($errores['piso'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['piso']) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="depto">Departamento (opcional)</label>
                    <input
                        type="text"
                        class="form-control<?= isset($errores['depto']) ? ' is-invalid' : '' ?>"
                        id="depto"
                        name="depto"
                        value="<?= esc($anterior['depto'] ?? $inmueble['depto'] ?? '') ?>"
                        maxlength="10"
                    >
                    <?php if (isset($errores['depto'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['depto']) ?></div>
                    <?php endif ?>
                </div>
                <div class="form-group col-md-4">
                    <label for="ciudad">Ciudad</label>
                    <input
                        type="text"
                        class="form-control<?= isset($errores['ciudad']) ? ' is-invalid' : '' ?>"
                        id="ciudad"
                        name="ciudad"
                        value="<?= esc($anterior['ciudad'] ?? $inmueble['ciudad']) ?>"
                        maxlength="60"
                        required
                    >
                    <?php if (isset($errores['ciudad'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['ciudad']) ?></div>
                    <?php endif ?>
                </div>
                <div class="form-group col-md-4">
                    <label for="superficie">Superficie (m²)</label>
                    <input
                        type="number"
                        class="form-control<?= isset($errores['superficie']) ? ' is-invalid' : '' ?>"
                        id="superficie"
                        name="superficie"
                        value="<?= esc($anterior['superficie'] ?? $inmueble['superficie']) ?>"
                        min="0.01"
                        step="0.01"
                        required
                    >
                    <?php if (isset($errores['superficie'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['superficie']) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <div class="form-group">
                <label for="estado">Estado</label>
                <select
                    class="form-control<?= isset($errores['estado']) ? ' is-invalid' : '' ?>"
                    id="estado"
                    name="estado"
                    <?= $inmueble['estado'] === 'inactivo' ? 'disabled' : '' ?>
                    required
                >
                    <?php if ($inmueble['estado'] === 'inactivo') : ?>
                        <option value="inactivo" selected>Inactivo</option>
                    <?php else : ?>
                        <option value="">Seleccione un estado</option>
                        <?php foreach (['ocupado', 'disponible', 'en mantenimiento'] as $estado) : ?>
                            <option
                                value="<?= esc($estado) ?>"
                                <?= ($anterior['estado'] ?? $inmueble['estado']) === $estado ? 'selected' : '' ?>
                            >
                                <?= esc(etiqueta_estado_propiedad($estado)) ?>
                            </option>
                        <?php endforeach ?>
                    <?php endif ?>
                </select>
                <?php if ($inmueble['estado'] === 'inactivo') : ?>
                    <input type="hidden" name="estado" value="inactivo">
                <?php endif ?>
                <?php if (isset($errores['estado'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['estado']) ?></div>
                <?php endif ?>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= route_to('inmuebles.show', $inmueble['id_propiedad']) ?>" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
