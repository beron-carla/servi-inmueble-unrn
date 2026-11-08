<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card card-primary">
    <div class="card-header">
        <h2 class="card-title">Modificar datos del usuario</h2>
    </div>
    <form action="<?= route_to('admin.usuarios.update', $usuario['id_usuario']) ?>" method="post">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="form-group">
                <label for="nombre_completo">Nombre completo</label>
                <input
                    type="text"
                    class="form-control<?= isset($errores['nombre_completo']) ? ' is-invalid' : '' ?>"
                    id="nombre_completo"
                    name="nombre_completo"
                    value="<?= esc($anterior['nombre_completo'] ?? $usuario['nombre_completo']) ?>"
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
                    value="<?= esc($anterior['mail'] ?? $usuario['mail']) ?>"
                    maxlength="100"
                    autocomplete="email"
                    required
                >
                <?php if (isset($errores['mail'])) : ?>
                    <div class="invalid-feedback"><?= esc($errores['mail']) ?></div>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="rol">Rol</label>
                <?php if ($esUsuarioActual) : ?>
                    <input type="hidden" name="rol" value="admin">
                    <input type="text" class="form-control" value="Administrador" disabled>
                    <small class="form-text text-muted">No puede quitarse el rol Administrador a sí mismo.</small>
                <?php else : ?>
                    <select
                        class="form-control<?= isset($errores['rol']) ? ' is-invalid' : '' ?>"
                        id="rol"
                        name="rol"
                        required
                    >
                        <option value="admin" <?= ($anterior['rol'] ?? $usuario['rol']) === 'admin' ? 'selected' : '' ?>>
                            Administrador
                        </option>
                        <option value="propietario" <?= ($anterior['rol'] ?? $usuario['rol']) === 'propietario' ? 'selected' : '' ?>>
                            Propietario
                        </option>
                    </select>
                    <?php if (isset($errores['rol'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['rol']) ?></div>
                    <?php endif ?>
                <?php endif ?>
            </div>

            <div class="form-group">
                <label for="estado">Estado</label>
                <?php if ($esUsuarioActual) : ?>
                    <input type="hidden" name="estado" value="activo">
                    <input type="text" class="form-control" value="Activo" disabled>
                    <small class="form-text text-muted">No puede bloquearse ni darse de baja a sí mismo.</small>
                <?php else : ?>
                    <select
                        class="form-control<?= isset($errores['estado']) ? ' is-invalid' : '' ?>"
                        id="estado"
                        name="estado"
                        required
                    >
                        <option value="activo" <?= ($anterior['estado'] ?? $usuario['estado']) === 'activo' ? 'selected' : '' ?>>
                            Activo
                        </option>
                        <option value="bloqueado" <?= ($anterior['estado'] ?? $usuario['estado']) === 'bloqueado' ? 'selected' : '' ?>>
                            Bloqueado
                        </option>
                        <option value="baja" <?= ($anterior['estado'] ?? $usuario['estado']) === 'baja' ? 'selected' : '' ?>>
                            Baja
                        </option>
                    </select>
                    <?php if (isset($errores['estado'])) : ?>
                        <div class="invalid-feedback"><?= esc($errores['estado']) ?></div>
                    <?php endif ?>
                <?php endif ?>
            </div>
        </div>

        <div class="card-footer">
            <button type="submit" class="btn btn-primary">Guardar cambios</button>
            <a href="<?= route_to('admin.usuarios.show', $usuario['id_usuario']) ?>" class="btn btn-secondary">
                Cancelar
            </a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
