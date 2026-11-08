<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Datos del usuario</h2>
    </div>
    <form action="<?= route_to('admin.usuarios.store') ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
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
                <label for="rol">Rol</label>
                <select
                    class="form-control<?= isset($errores['rol']) ? ' is-invalid' : '' ?>"
                    id="rol"
                    name="rol"
                    required
                >
                    <option value="">Seleccione un rol</option>
                    <option value="admin" <?= ($anterior['rol'] ?? '') === 'admin' ? 'selected' : '' ?>>
                        Administrador
                    </option>
                    <option value="propietario" <?= ($anterior['rol'] ?? '') === 'propietario' ? 'selected' : '' ?>>
                        Propietario
                    </option>
                </select>
                <?php if (isset($errores['rol'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['rol']) ?></div>
                <?php endif ?>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Crear usuario</button>
            <a href="<?= route_to('admin.usuarios.index') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
