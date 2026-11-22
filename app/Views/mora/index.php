<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Facturas en mora</h2>
    </div>
    <form action="<?= route_to('mora.index') ?>" method="get">
        <div class="card-body">
            <div class="row">
                <?php if (es_admin()) : ?>
                    <div class="col-md-3 form-group">
                        <label for="propietario">Propietario</label>
                        <select class="form-control" id="propietario" name="propietario">
                            <option value="">Todos</option>
                            <?php foreach ($propietarios as $propietario) : ?>
                                <option value="<?= esc((string) $propietario['id_usuario'], 'attr') ?>" <?= $filtros['propietario'] === (string) $propietario['id_usuario'] ? 'selected' : '' ?>>
                                    <?= esc($propietario['nombre_completo']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                <?php endif ?>
                <div class="col-md-3 form-group">
                    <label for="inmueble">Inmueble</label>
                    <select class="form-control" id="inmueble" name="inmueble">
                        <option value="">Todos</option>
                        <?php foreach ($inmuebles as $inmueble) : ?>
                            <option value="<?= esc((string) $inmueble['id_propiedad'], 'attr') ?>" <?= $filtros['inmueble'] === (string) $inmueble['id_propiedad'] ? 'selected' : '' ?>>
                                <?= esc($inmueble['alias']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label for="desde">Vencimiento desde</label>
                    <input class="form-control" type="date" id="desde" name="desde" value="<?= esc($filtros['desde'], 'attr') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label for="hasta">Vencimiento hasta</label>
                    <input class="form-control" type="date" id="hasta" name="hasta" value="<?= esc($filtros['hasta'], 'attr') ?>">
                </div>
                <div class="col-md-1 form-group">
                    <label for="dias_min">Días mín.</label>
                    <input class="form-control" type="number" min="0" id="dias_min" name="dias_min" value="<?= esc($filtros['dias_min'], 'attr') ?>">
                </div>
                <div class="col-md-1 form-group">
                    <label for="dias_max">Días máx.</label>
                    <input class="form-control" type="number" min="0" id="dias_max" name="dias_max" value="<?= esc($filtros['dias_max'], 'attr') ?>">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= route_to('mora.index') ?>" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-body table-responsive p-0">
        <?php if ($facturas === []) : ?>
            <p class="p-3 mb-0">No hay facturas en mora con los filtros seleccionados</p>
        <?php else : ?>
            <table class="table table-bordered table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Propietario</th>
                        <th>Inmueble</th>
                        <th>Servicio</th>
                        <th>Período</th>
                        <th>Vencimiento</th>
                        <th>Días de mora</th>
                        <th>Monto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($facturas as $factura) : ?>
                        <tr>
                            <td><?= esc($factura['propietario']) ?></td>
                            <td><?= esc($factura['inmueble']) ?></td>
                            <td><?= esc(etiqueta_tipo_servicio((string) $factura['servicio'])) ?></td>
                            <td>
                                <?= $factura['periodo'] === 'mes'
                                    ? esc($factura['periodo_mes'] . '/' . $factura['periodo_anio'])
                                    : esc((string) $factura['periodo_anio']) ?>
                            </td>
                            <td><?= esc(formato_fecha((string) $factura['vencimiento_aplicable'])) ?></td>
                            <td><?= esc((string) max(0, (int) $factura['dias_mora'])) ?></td>
                            <td><?= esc(formato_moneda((float) $factura['monto_total'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
