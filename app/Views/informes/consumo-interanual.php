<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Filtros</h2>
    </div>
    <form action="<?= route_to('informes.consumo-interanual') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="inmueble">Inmueble</label>
                    <select class="form-control" id="inmueble" name="inmueble" required>
                        <option value="">Seleccione</option>
                        <?php foreach ($inmuebles as $opcion) : ?>
                            <option value="<?= esc((string) $opcion['id_propiedad'], 'attr') ?>" <?= $filtros['inmueble'] === (string) $opcion['id_propiedad'] ? 'selected' : '' ?>>
                                <?= esc($opcion['alias']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-3 form-group">
                    <label for="servicio">Servicio</label>
                    <select class="form-control" id="servicio" name="servicio">
                        <option value="">Todos</option>
                        <?php foreach ($servicios as $opcion) : ?>
                            <option value="<?= esc((string) $opcion['id_propiedad_servicio'], 'attr') ?>" <?= $filtros['servicio'] === (string) $opcion['id_propiedad_servicio'] ? 'selected' : '' ?>>
                                <?= esc(etiqueta_tipo_servicio((string) $opcion['tipo'])) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="anio_base">Año base</label>
                    <input class="form-control" type="number" min="1901" max="2155" id="anio_base" name="anio_base" value="<?= esc($filtros['anio_base'], 'attr') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label for="anio_comparado">Año comparado</label>
                    <input class="form-control" type="number" min="1901" max="2155" id="anio_comparado" name="anio_comparado" value="<?= esc($filtros['anio_comparado'], 'attr') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label for="unidad">Unidad de medida</label>
                    <select class="form-control" id="unidad" name="unidad">
                        <option value="">Todas</option>
                        <?php foreach ($unidades as $unidad) : ?>
                            <option value="<?= esc($unidad, 'attr') ?>" <?= $filtros['unidad'] === $unidad ? 'selected' : '' ?>>
                                <?= esc(etiqueta_unidad($unidad)) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Comparar</button>
            <a href="<?= route_to('informes.consumo-interanual') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if ($inmueble_requerido) : ?>
    <div class="card">
        <div class="card-body">No hay datos de consumo para los filtros seleccionados</div>
    </div>
<?php elseif (! $hay_datos) : ?>
    <div class="card">
        <div class="card-body">No hay datos de consumo para los filtros seleccionados</div>
    </div>
<?php else : ?>
    <?php
    $labels = [];
    $base = [];
    $comparado = [];
    foreach ($meses as $fila) {
        if ($fila['mes'] === 'Total') {
            continue;
        }
        $labels[] = $fila['mes'];
        $base[] = $fila['base'];
        $comparado[] = $fila['comparado'];
    }
    ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?= esc($etiqueta_valor) ?> mensual</h3>
        </div>
        <div class="card-body">
            <canvas id="grafico-consumo-interanual"
                data-label-base="<?= esc($filtros['anio_base'], 'attr') ?>"
                data-label-comparado="<?= esc($filtros['anio_comparado'], 'attr') ?>"
                data-etiqueta="<?= esc($etiqueta_valor, 'attr') ?>"
                data-labels="<?= esc(json_encode($labels, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                data-base="<?= esc(json_encode($base), 'attr') ?>"
                data-comparado="<?= esc(json_encode($comparado), 'attr') ?>"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalle mensual</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Mes</th>
                        <th><?= esc($filtros['anio_base']) ?></th>
                        <th><?= esc($filtros['anio_comparado']) ?></th>
                        <th>Diferencia</th>
                        <th>Variación porcentual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meses as $fila) : ?>
                        <tr>
                            <td><?= esc($fila['mes']) ?></td>
                            <td>
                                <?= $es_monto
                                    ? esc(formato_moneda((float) $fila['base']))
                                    : esc(number_format((float) $fila['base'], 2, ',', '.')) ?>
                            </td>
                            <td>
                                <?= $es_monto
                                    ? esc(formato_moneda((float) $fila['comparado']))
                                    : esc(number_format((float) $fila['comparado'], 2, ',', '.')) ?>
                            </td>
                            <td>
                                <?= $es_monto
                                    ? esc(formato_moneda((float) $fila['diferencia']))
                                    : esc(number_format((float) $fila['diferencia'], 2, ',', '.')) ?>
                            </td>
                            <td>
                                <?= $fila['variacion'] === null
                                    ? '—'
                                    : esc(formato_porcentaje((float) $fila['variacion'])) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>

<?php if ($anuales !== []) : ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Resumen de facturas anuales</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Año</th>
                        <th>Cantidad</th>
                        <th><?= esc($etiqueta_valor) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anuales as $anual) : ?>
                        <tr>
                            <td><?= esc((string) $anual['periodo_anio']) ?></td>
                            <td><?= esc((string) $anual['cantidad']) ?></td>
                            <td>
                                <?= $es_monto
                                    ? esc(formato_moneda((float) $anual['valor']))
                                    : esc(number_format((float) $anual['valor'], 2, ',', '.')) ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/informes.js') ?>"></script>
<?= $this->endSection() ?>
