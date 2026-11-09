<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php $valores = $anterior !== [] ? $anterior : $tipo ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Datos del tipo de servicio</h2>
    </div>
    <form action="<?= route_to('admin.tipos.update', $tipo['id_tipo_servicio']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-group">
                <label for="tipo">Nombre</label>
                <select
                    class="form-control<?= isset($errores['tipo']) ? ' is-invalid' : '' ?>"
                    id="tipo"
                    name="tipo"
                    data-unidades-select
                    required
                >
                    <option value="">Seleccione un tipo</option>
                    <?php foreach ($opciones['tipos'] as $opcionTipo) : ?>
                        <option
                            value="<?= esc($opcionTipo) ?>"
                            data-unidades="<?= esc(implode('|', $opciones['unidades_por_tipo'][$opcionTipo]), 'attr') ?>"
                            <?= ($valores['tipo'] ?? '') === $opcionTipo ? 'selected' : '' ?>
                        >
                            <?= esc(etiqueta_tipo_servicio($opcionTipo)) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <?php if (isset($errores['tipo'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['tipo']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="categoria">Categoría</label>
                <select
                    class="form-control<?= isset($errores['categoria']) ? ' is-invalid' : '' ?>"
                    id="categoria"
                    name="categoria"
                    required
                >
                    <option value="">Seleccione una categoría</option>
                    <option value="publico" <?= ($valores['categoria'] ?? '') === 'publico' ? 'selected' : '' ?>>
                        Público
                    </option>
                    <option value="privado" <?= ($valores['categoria'] ?? '') === 'privado' ? 'selected' : '' ?>>
                        Privado
                    </option>
                </select>
                <?php if (isset($errores['categoria'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['categoria']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="unidad_medida">Unidad de medida</label>
                <select
                    class="form-control<?= isset($errores['unidad_medida']) ? ' is-invalid' : '' ?>"
                    id="unidad_medida"
                    name="unidad_medida"
                    data-unidad-medida
                    data-initial-value="<?= esc($valores['unidad_medida'] ?? '', 'attr') ?>"
                    required
                >
                    <option value="">Seleccione una unidad</option>
                    <?php foreach ($opciones['unidades'] as $unidad) : ?>
                        <option
                            value="<?= esc($unidad) ?>"
                            <?= ($valores['unidad_medida'] ?? '') === $unidad ? 'selected' : '' ?>
                        >
                            <?= esc(etiqueta_unidad($unidad)) ?>
                        </option>
                    <?php endforeach ?>
                </select>
                <?php if (isset($errores['unidad_medida'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['unidad_medida']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="proveedor_predeterminado">Proveedor predeterminado</label>
                <input
                    type="text"
                    class="form-control<?= isset($errores['proveedor_predeterminado']) ? ' is-invalid' : '' ?>"
                    id="proveedor_predeterminado"
                    name="proveedor_predeterminado"
                    value="<?= esc($valores['proveedor_predeterminado'] ?? '') ?>"
                    maxlength="45"
                    required
                >
                <?php if (isset($errores['proveedor_predeterminado'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['proveedor_predeterminado']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="activo">Estado</label>
                <select
                    class="form-control<?= isset($errores['activo']) ? ' is-invalid' : '' ?>"
                    id="activo"
                    name="activo"
                    required
                >
                    <option value="1" <?= (string) ($valores['activo'] ?? '') === '1' ? 'selected' : '' ?>>
                        Activo
                    </option>
                    <option value="0" <?= (string) ($valores['activo'] ?? '') === '0' ? 'selected' : '' ?>>
                        Inactivo
                    </option>
                </select>
                <?php if (isset($errores['activo'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['activo']) ?></div>
                <?php endif ?>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= route_to('admin.tipos.show', $tipo['id_tipo_servicio']) ?>" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/tipos-servicio.js') ?>"></script>
<?= $this->endSection() ?>
