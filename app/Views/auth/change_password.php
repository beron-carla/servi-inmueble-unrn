<?= $this->extend('layout/auth') ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header text-center">
        <span class="h1"><b>Servi</b>Inmueble</span>
    </div>
    <div class="card-body">
        <p class="login-box-msg">Debés cambiar tu contraseña para continuar</p>

        <form action="<?= route_to('auth.password.update') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="contrasenia">Nueva contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="contrasenia"
                    name="contrasenia"
                    autocomplete="new-password"
                    required
                >
                <small class="form-text text-muted">Mínimo 8 caracteres, con al menos una letra y un número.</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Cambiar contraseña</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
