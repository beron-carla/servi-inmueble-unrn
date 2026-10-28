<?php
// Secciones disponibles: content, styles y scripts.
// El título se pasa desde el controlador mediante la variable $titulo.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'ServiInmueble 2026') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte/dist/css/adminlte.min.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?= $this->include('layout/header') ?>
    <?= $this->include('layout/sidebar') ?>

    <div class="content-wrapper">
        <section class="content">
            <div class="container-fluid">
                <?php if (isset($titulo)) : ?>
                    <h1 class="h3 pt-3 mb-3"><?= esc($titulo) ?></h1>
                <?php endif ?>
                <?= $this->include('partials/alerts') ?>
                <?= $this->renderSection('content') ?>
            </div>
        </section>
    </div>

    <?= $this->include('layout/footer') ?>
</div>
<script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= base_url('assets/adminlte/dist/js/adminlte.min.js') ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
