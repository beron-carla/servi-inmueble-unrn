<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <p class="mb-0">Bienvenido/a, <?= esc(session()->get('nombre_completo')) ?>.</p>
    </div>
</div>
<?= $this->endSection() ?>
