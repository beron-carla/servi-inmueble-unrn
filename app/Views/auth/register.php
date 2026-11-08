<?= $this->extend('layout/auth') ?>

<?= $this->section('content') ?>
<?php
$errores = session()->getFlashdata('registro_errores') ?? [];
$anterior = session()->getFlashdata('registro_anterior') ?? [];
?>
<div class="card card-outline card-primary">
    <div class="card-header text-center">
        <a href="<?= site_url() ?>" class="h1"><b>Servi</b>Inmueble</a>
    </div>
    <div class="card-body">
        <p class="login-box-msg">Creá una cuenta para acceder al sistema</p>

        <form action="<?= route_to('auth.register.store') ?>" method="post">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="nombre_completo">Nombre completo</label>
                <input
                    type="text"
                    class="form-control<?= isset($errores['nombre_completo']) ? ' is-invalid' : '' ?>"
                    id="nombre_completo"
                    name="nombre_completo"
                    value="<?= esc($anterior['nombre_completo'] ?? '') ?>"
                    maxlength="50"
                    autocomplete="name"
                    required
                >
                <?php if (isset($errores['nombre_completo'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['nombre_completo']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="mail">Correo electrónico</label>
                <input
                    type="email"
                    class="form-control<?= isset($errores['mail']) ? ' is-invalid' : '' ?>"
                    id="mail"
                    name="mail"
                    value="<?= esc($anterior['mail'] ?? '') ?>"
                    maxlength="100"
                    autocomplete="email"
                    required
                >
                <?php if (isset($errores['mail'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['mail']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="contrasenia">Contraseña</label>
                <input
                    type="password"
                    class="form-control<?= isset($errores['contrasenia']) ? ' is-invalid' : '' ?>"
                    id="contrasenia"
                    name="contrasenia"
                    autocomplete="new-password"
                    required
                >
                <small class="form-text text-muted">Mínimo 8 caracteres, con al menos una letra y un número.</small>
                <?php if (isset($errores['contrasenia'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['contrasenia']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <div class="form-check">
                    <input
                        type="checkbox"
                        class="form-check-input<?= isset($errores['terminos']) ? ' is-invalid' : '' ?>"
                        id="acepta_terminos"
                        name="acepta_terminos"
                        value="1"
                        <?= ($anterior['acepta_terminos'] ?? false) ? 'checked' : '' ?>
                        required
                    >
                    <label class="form-check-label" for="acepta_terminos">Acepto los términos y condiciones</label>
                    <?php if (isset($errores['terminos'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['terminos']) ?></div>
                    <?php endif ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Registrarse</button>
        </form>

        <p class="mt-3 mb-0 text-center">
            <a href="<?= route_to('auth.login') ?>">Ya tengo una cuenta</a>
        </p>
    </div>
</div>
<?= $this->endSection() ?>
