<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($titulo ?? 'ServiInmueble 2026') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/plugins/fontawesome-free/css/all.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/adminlte/dist/css/adminlte.min.css') ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body class="hold-transition login-page">
    <main class="login-box">
        <?= $this->include('partials/alerts') ?>
        <?= $this->renderSection('content') ?>
    </main>
    <script src="<?= base_url('assets/plugins/jquery/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/adminlte/dist/js/adminlte.min.js') ?>"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>
