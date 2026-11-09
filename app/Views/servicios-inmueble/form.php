<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title"><?= esc($inmueble['alias']) ?></h2>
    </div>
    <form action="<?= esc($accion, 'attr') ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <?php if ($sinTipos) : ?>
                <div class="alert alert-warning" role="alert">
                    No hay tipos de servicio disponibles. Contacte al administrador.
                </div>
            <?php else : ?>
                <?php if ($servicio === null) : ?>
                    <div class="form-group">
                        <label for="id_tipo_servicio">Tipo de servicio</label>
                        <select
                            class="form-control<?= isset($errores['id_tipo_servicio']) ? ' is-invalid' : '' ?>"
                            id="id_tipo_servicio"
                            name="id_tipo_servicio"
                            required
                        >
                            <option value="">Seleccione un tipo de servicio</option>
                            <?php foreach ($tipos as $tipo) : ?>
                                <?php
                                $idTipo = (string) $tipo['id_tipo_servicio'];
                                $etiqueta = etiqueta_tipo_servicio($tipo['tipo'])
                                    . ' — ' . $tipo['proveedor_predeterminado'];
                                ?>
                                <option
                                    value="<?= esc($idTipo, 'attr') ?>"
                                    <?= ($anterior['id_tipo_servicio'] ?? '') === $idTipo ? 'selected' : '' ?>
                                >
                                    <?= esc($etiqueta) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                        <?php if (isset($errores['id_tipo_servicio'])) : ?>
                            <div class="invalid-feedback"><?= esc($errores['id_tipo_servicio']) ?></div>
                        <?php endif ?>
                    </div>
                <?php else : ?>
                    <div class="form-group">
                        <label>Tipo de servicio</label>
                        <p class="form-control-plaintext">
                            <?= esc(etiqueta_tipo_servicio($servicio['tipo'])) ?>
                            — <?= esc($servicio['proveedor_predeterminado']) ?>
                        </p>
                    </div>
                <?php endif ?>

                <?php
                $campos = [
                    'codigo_pago_electronico' => ['CPE/Pagar', 20],
                    'numero_cuenta_contrato' => ['Número de cuenta', 20],
                    'numero_medidor' => ['Número de medidor (opcional)', 20],
                    'domicilio_facturacion' => ['Domicilio de facturación', 45],
                    'titular' => ['Titular', 50],
                ];
                ?>
                <?php foreach ($campos as $campo => [$etiqueta, $maximo]) : ?>
                    <?php $valor = $anterior[$campo] ?? ($servicio[$campo] ?? ''); ?>
                    <div class="form-group">
                        <label for="<?= esc($campo, 'attr') ?>"><?= esc($etiqueta) ?></label>
                        <input
                            type="text"
                            class="form-control<?= isset($errores[$campo]) ? ' is-invalid' : '' ?>"
                            id="<?= esc($campo, 'attr') ?>"
                            name="<?= esc($campo, 'attr') ?>"
                            value="<?= esc($valor, 'attr') ?>"
                            maxlength="<?= esc((string) $maximo, 'attr') ?>"
                            <?= $campo === 'numero_medidor' ? '' : 'required' ?>
                        >
                        <?php if (isset($errores[$campo])) : ?>
                            <div class="invalid-feedback"><?= esc($errores[$campo]) ?></div>
                        <?php endif ?>
                    </div>
                <?php endforeach ?>
            <?php endif ?>
        </div>
        <div class="card-footer">
            <?php if (! $sinTipos) : ?>
                <button type="submit" class="btn btn-primary">Guardar</button>
            <?php endif ?>
            <a
                href="<?= route_to('inmuebles.show', $inmueble['id_propiedad']) ?>"
                class="btn btn-secondary"
            >Volver al inmueble</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
