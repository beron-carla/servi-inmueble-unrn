<?= $this->extend('layout/auth') ?>

<?= $this->section('content') ?>
<div class="card card-outline card-primary">
    <div class="card-header text-center">
        <a href="<?= site_url() ?>" class="h1"><b>Servi</b>Inmueble</a>
    </div>
    <div class="card-body">
        <p class="login-box-msg">Ingresá para iniciar tu sesión</p>

        <form action="<?= route_to('auth.login.attempt') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="mail">Correo electrónico</label>
                <input
                    type="email"
                    class="form-control"
                    id="mail"
                    name="mail"
                    value="<?= esc(session()->getFlashdata('login_mail') ?? '') ?>"
                    maxlength="100"
                    autocomplete="username"
                    required
                >
            </div>

            <div class="form-group">
                <label for="contrasenia">Contraseña</label>
                <input
                    type="password"
                    class="form-control"
                    id="contrasenia"
                    name="contrasenia"
                    autocomplete="current-password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">Iniciar sesión</button>
        </form>

        <p class="mt-3 mb-0 text-center">
            <a href="<?= site_url(route_to('auth.register')) ?>">Crear una cuenta</a>
        </p>
    </div>
</div>
<?= $this->endSection() ?>
