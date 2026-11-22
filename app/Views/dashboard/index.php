<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <p class="mb-0">Bienvenido/a, <?= esc(session()->get('nombre_completo')) ?>.</p>
        <a class="btn btn-outline-warning mt-3" href="<?= route_to('mora.index') ?>">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            Facturas en mora
        </a>
    </div>
</div>
<?= $this->endSection() ?>
