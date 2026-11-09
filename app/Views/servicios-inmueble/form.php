<?php
$valor = static function (string $campo) use ($anterior, $servicio): string {
    return (string) ($anterior[$campo] ?? $servicio[$campo] ?? '');
};
$seleccionado = $valor('id_tipo_servicio');
?>
<form action="<?= esc($action) ?>" method="post">
    <?= csrf_field() ?>

    <div class="form-group">
        <label for="id_tipo_servicio">Tipo de servicio</label>
        <select
            class="form-control <?= isset($errores['id_tipo_servicio']) ? 'is-invalid' : '' ?>"
            id="id_tipo_servicio"
            name="id_tipo_servicio"
            required
        >
            <option value="">Seleccione un tipo</option>
            <?php foreach ($tipos as $tipo) : ?>
                <option
                    value="<?= esc($tipo['id_tipo_servicio']) ?>"
                    <?= $seleccionado === (string) $tipo['id_tipo_servicio'] ? 'selected' : '' ?>
                >
                    <?= esc(etiqueta_tipo_servicio($tipo['tipo'])) ?> · <?= esc($tipo['proveedor_predeterminado']) ?>
                </option>
            <?php endforeach ?>
        </select>
        <?php if (isset($errores['id_tipo_servicio'])) : ?>
            <div class="invalid-feedback"><?= esc($errores['id_tipo_servicio']) ?></div>
        <?php endif ?>
    </div>

    <?php foreach ([
        'codigo_pago_electronico' => ['CPE/Pagar', 20, true],
        'numero_cuenta_contrato' => ['Número de cuenta', 20, true],
        'numero_medidor' => ['Número de medidor (opcional)', 20, false],
        'domicilio_facturacion' => ['Domicilio de facturación', 45, true],
        'titular' => ['Titular', 50, true],
    ] as $campo => [$etiqueta, $maximo, $obligatorio]) : ?>
        <div class="form-group">
            <label for="<?= esc($campo) ?>"><?= esc($etiqueta) ?></label>
            <input
                type="text"
                class="form-control <?= isset($errores[$campo]) ? 'is-invalid' : '' ?>"
                id="<?= esc($campo) ?>"
                name="<?= esc($campo) ?>"
                maxlength="<?= esc($maximo) ?>"
                value="<?= esc($valor($campo)) ?>"
                <?= $obligatorio ? 'required' : '' ?>
            >
            <?php if (isset($errores[$campo])) : ?>
                <div class="invalid-feedback"><?= esc($errores[$campo]) ?></div>
            <?php endif ?>
        </div>
    <?php endforeach ?>

    <button type="submit" class="btn btn-primary"><?= esc($boton) ?></button>
</form>
