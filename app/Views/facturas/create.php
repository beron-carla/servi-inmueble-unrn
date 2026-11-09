<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<?php
$periodo = $anterior['periodo'] ?? 'mes';
$periodoMes = $anterior['periodo_mes'] ?? date('m');
$periodoAnio = $anterior['periodo_anio'] ?? date('Y');
$meses = [
    '01' => 'Enero',
    '02' => 'Febrero',
    '03' => 'Marzo',
    '04' => 'Abril',
    '05' => 'Mayo',
    '06' => 'Junio',
    '07' => 'Julio',
    '08' => 'Agosto',
    '09' => 'Septiembre',
    '10' => 'Octubre',
    '11' => 'Noviembre',
    '12' => 'Diciembre',
];
?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">
            <?= esc($servicio['alias_propiedad']) ?> —
            <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?> —
            <?= esc($servicio['proveedor_predeterminado']) ?>
        </h2>
    </div>
    <form action="<?= route_to('facturas.store', $servicio['id_propiedad_servicio']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-group">
                <label for="periodo">Período</label>
                <select
                    class="form-control<?= isset($errores['periodo']) ? ' is-invalid' : '' ?>"
                    id="periodo"
                    name="periodo"
                    required
                >
                    <option value="mes" <?= $periodo === 'mes' ? 'selected' : '' ?>>Mensual</option>
                    <option value="anio" <?= $periodo === 'anio' ? 'selected' : '' ?>>Anual</option>
                </select>
                <?php if (isset($errores['periodo'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['periodo']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="periodo_mes">Mes (solo para período mensual)</label>
                <select
                    class="form-control<?= isset($errores['periodo_mes']) ? ' is-invalid' : '' ?>"
                    id="periodo_mes"
                    name="periodo_mes"
                >
                    <?php if ($periodo === 'mes' && ! isset($meses[$periodoMes])) : ?>
                        <option value="<?= esc($periodoMes, 'attr') ?>" selected>
                            Valor ingresado: <?= esc($periodoMes) ?>
                        </option>
                    <?php endif ?>
                    <?php foreach ($meses as $numero => $nombre) : ?>
                        <option
                            value="<?= esc($numero, 'attr') ?>"
                            <?= $periodoMes === $numero ? 'selected' : '' ?>
                        ><?= esc($nombre) ?></option>
                    <?php endforeach ?>
                </select>
                <?php if (isset($errores['periodo_mes'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['periodo_mes']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="periodo_anio">Año del período</label>
                <input
                    type="number"
                    class="form-control<?= isset($errores['periodo_anio']) ? ' is-invalid' : '' ?>"
                    id="periodo_anio"
                    name="periodo_anio"
                    min="1901"
                    max="2155"
                    step="1"
                    value="<?= esc($periodoAnio, 'attr') ?>"
                    required
                >
                <?php if (isset($errores['periodo_anio'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['periodo_anio']) ?></div>
                <?php endif ?>
            </div>

            <?php
            $fechas = [
                'fecha_emision' => ['Fecha de emisión', true],
                'fecha_primer_vencimiento' => ['Fecha del primer vencimiento', true],
                'fecha_segundo_vencimiento' => ['Fecha del segundo vencimiento (opcional)', false],
            ];
            ?>
            <?php foreach ($fechas as $campo => [$etiqueta, $obligatorio]) : ?>
                <div class="form-group">
                    <label for="<?= esc($campo, 'attr') ?>"><?= esc($etiqueta) ?></label>
                    <input
                        type="date"
                        class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>"
                        id="<?= esc($campo, 'attr') ?>"
                        name="<?= esc($campo, 'attr') ?>"
                        value="<?= esc($anterior[$campo] ?? '', 'attr') ?>"
                        <?= $obligatorio ? 'required' : '' ?>
                    >
                    <?php if (isset($errores[$campo])) : ?>
                        <div class="invalid-feedback"><?= esc($errores[$campo]) ?></div>
                    <?php endif ?>
                </div>
            <?php endforeach ?>

            <div class="form-group">
                <label for="monto_total">Monto</label>
                <input
                    type="number"
                    class="form-control<?= isset($errores['monto_total']) ? ' is-invalid' : '' ?>"
                    id="monto_total"
                    name="monto_total"
                    min="0.01"
                    step="0.01"
                    value="<?= esc($anterior['monto_total'] ?? '', 'attr') ?>"
                    required
                >
                <?php if (isset($errores['monto_total'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['monto_total']) ?></div>
                <?php endif ?>
            </div>

            <?php if ($servicio['numero_medidor'] !== null && trim((string) $servicio['numero_medidor']) !== '') : ?>
                <p class="text-muted">
                    Medidor <?= esc($servicio['numero_medidor']) ?>:
                    el consumo se calcula con la diferencia entre las lecturas.
                </p>
                <?php foreach ([
                    'lectura_anterior' => 'Lectura anterior',
                    'lectura_actual' => 'Lectura actual',
                ] as $campo => $etiqueta) : ?>
                    <div class="form-group">
                        <label for="<?= esc($campo, 'attr') ?>"><?= esc($etiqueta) ?></label>
                        <input
                            type="number"
                            class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>"
                            id="<?= esc($campo, 'attr') ?>"
                            name="<?= esc($campo, 'attr') ?>"
                            min="0"
                            step="1"
                            value="<?= esc($anterior[$campo] ?? '', 'attr') ?>"
                            required
                        >
                        <?php if (isset($errores[$campo])) : ?>
                            <div class="invalid-feedback"><?= esc($errores[$campo]) ?></div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            <?php else : ?>
                <div class="form-group">
                    <label for="consumo_del_periodo">
                        Consumo del período (<?= esc(etiqueta_unidad($servicio['unidad_medida'])) ?>)
                    </label>
                    <input
                        type="number"
                        class="form-control<?= isset($errores['consumo_del_periodo']) ? ' is-invalid' : '' ?>"
                        id="consumo_del_periodo"
                        name="consumo_del_periodo"
                        min="0"
                        step="1"
                        value="<?= esc($anterior['consumo_del_periodo'] ?? '', 'attr') ?>"
                        required
                    >
                    <?php if (isset($errores['consumo_del_periodo'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['consumo_del_periodo']) ?></div>
                    <?php endif ?>
                </div>
                <p class="text-muted">Este servicio no tiene medidor; las lecturas son opcionales.</p>
            <?php endif ?>

            <dl class="row">
                <dt class="col-sm-3">Unidad de medida</dt>
                <dd class="col-sm-9"><?= esc(etiqueta_unidad($servicio['unidad_medida'])) ?></dd>
                <dt class="col-sm-3">Proveedor</dt>
                <dd class="col-sm-9"><?= esc($servicio['proveedor_predeterminado']) ?></dd>
                <dt class="col-sm-3">Categoría</dt>
                <dd class="col-sm-9">
                    <?= esc($servicio['categoria'] === 'publico' ? 'Público' : 'Privado') ?>
                </dd>
            </dl>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Registrar factura</button>
            <a
                href="<?= route_to('servicios.show', $servicio['id_propiedad_servicio']) ?>"
                class="btn btn-secondary"
            >Cancelar</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
