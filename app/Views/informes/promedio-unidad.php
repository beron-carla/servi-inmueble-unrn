<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Filtros</h2>
    </div>
    <form action="<?= route_to('informes.promedio-unidad') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 form-group">
                    <label for="desde">Período desde</label>
                    <input class="form-control" type="month" id="desde" name="desde" value="<?= esc($filtros['desde'], 'attr') ?>">
                </div>
                <div class="col-md-3 form-group">
                    <label for="hasta">Período hasta</label>
                    <input class="form-control" type="month" id="hasta" name="hasta" value="<?= esc($filtros['hasta'], 'attr') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label for="inmueble">Inmueble</label>
                    <select class="form-control" id="inmueble" name="inmueble">
                        <option value="">Todos los inmuebles</option>
                        <?php foreach ($inmuebles as $opcion) : ?>
                            <option value="<?= esc((string) $opcion['id_propiedad'], 'attr') ?>" <?= $filtros['inmueble'] === (string) $opcion['id_propiedad'] ? 'selected' : '' ?>>
                                <?= esc($opcion['alias']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="servicio">Tipo de servicio</label>
                    <select class="form-control" id="servicio" name="servicio">
                        <option value="">Todos los servicios</option>
                        <?php foreach ($servicios as $opcion) : ?>
                            <option value="<?= esc((string) $opcion['id_propiedad_servicio'], 'attr') ?>" <?= $filtros['servicio'] === (string) $opcion['id_propiedad_servicio'] ? 'selected' : '' ?>>
                                <?= esc(etiqueta_tipo_servicio((string) $opcion['tipo'])) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="unidad">Unidad de medida</label>
                    <select class="form-control" id="unidad" name="unidad">
                        <option value="">Todas las unidades</option>
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
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= route_to('informes.promedio-unidad') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<?php if ($sin_datos) : ?>
    <div class="card">
        <div class="card-body">No hay datos para los filtros seleccionados</div>
    </div>
<?php endif ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Promedio por unidad de medida</h3>
    </div>
    <div class="card-body">
        <?php if ($grafico === []) : ?>
            <p class="mb-0">Sin datos</p>
        <?php else : ?>
            <?php
            $etiquetasGrafico = array_column($grafico, 'unidad');
            $promediosGrafico = array_column($grafico, 'promedio');
            $unidadesGrafico = array_column($grafico, 'unidad');
            ?>
            <canvas id="grafico-promedio-unidad"
                aria-label="Gráfico de barras con promedios por unidad de medida"
                data-labels="<?= esc(json_encode($etiquetasGrafico, JSON_UNESCAPED_UNICODE), 'attr') ?>"
                data-promedios="<?= esc(json_encode($promediosGrafico), 'attr') ?>"
                data-unidades="<?= esc(json_encode($unidadesGrafico, JSON_UNESCAPED_UNICODE), 'attr') ?>"></canvas>
        <?php endif ?>
    </div>
</div>

<?php if ($filas !== []) : ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Detalle mensual</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Unidad</th>
                        <th>Cantidad de facturas</th>
                        <th>Promedio</th>
                        <th>Mínimo</th>
                        <th>Máximo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filas as $fila) : ?>
                        <tr>
                            <td><?= esc(etiqueta_unidad((string) $fila['unidad'])) ?></td>
                            <td><?= esc((string) $fila['cantidad']) ?></td>
                            <?php foreach (['promedio', 'minimo', 'maximo'] as $campo) : ?>
                                <td>
                                    <?php if ($fila['unidad'] === 'Moneda fija') : ?>
                                        <?= $campo === 'promedio' ? 'Monto promedio: ' : '' ?><?= esc(formato_moneda((float) $fila[$campo])) ?>
                                    <?php else : ?>
                                        <?= esc(number_format((float) $fila[$campo], 2, ',', '.')) ?>
                                    <?php endif ?>
                                </td>
                            <?php endforeach ?>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif ?>

<?php if ($resumen_anual !== []) : ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Resumen de facturas anuales</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Unidad</th>
                        <th>Cantidad de facturas</th>
                        <th>Promedio</th>
                        <th>Mínimo</th>
                        <th>Máximo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resumen_anual as $fila) : ?>
                        <tr>
                            <td><?= esc(etiqueta_unidad((string) $fila['unidad'])) ?></td>
                            <td><?= esc((string) $fila['cantidad']) ?></td>
                            <?php foreach (['promedio', 'minimo', 'maximo'] as $campo) : ?>
                                <td>
                                    <?php if ($fila['unidad'] === 'Moneda fija') : ?>
                                        <?= $campo === 'promedio' ? 'Monto promedio: ' : '' ?><?= esc(formato_moneda((float) $fila[$campo])) ?>
                                    <?php else : ?>
                                        <?= esc(number_format((float) $fila[$campo], 2, ',', '.')) ?>
                                    <?php endif ?>
                                </td>
                            <?php endforeach ?>
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
