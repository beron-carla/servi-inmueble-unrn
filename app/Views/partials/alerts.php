<?php
$tiposAlertas = [
    'success' => 'success',
    'error'   => 'danger',
    'warning' => 'warning',
    'info'    => 'info',
];
?>
<?php foreach ($tiposAlertas as $tipo => $clase) : ?>
    <?php foreach ((array) session()->getFlashdata($tipo) as $mensaje) : ?>
        <?php if ($mensaje !== null && $mensaje !== '') : ?>
            <div class="alert alert-<?= esc($clase) ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
                <?= esc($mensaje) ?>
            </div>
        <?php endif ?>
    <?php endforeach ?>
<?php endforeach ?>
