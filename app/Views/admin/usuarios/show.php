<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Datos del usuario</h2>
    </div>
    <div class="card-body">
        <dl class="row">
            <dt class="col-sm-3">Nombre</dt>
            <dd class="col-sm-9"><?= esc($usuario['nombre_completo']) ?></dd>

            <dt class="col-sm-3">Correo electrónico</dt>
            <dd class="col-sm-9"><?= esc($usuario['mail']) ?></dd>

            <dt class="col-sm-3">Rol</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_rol($usuario['rol'])) ?></dd>

            <dt class="col-sm-3">Estado</dt>
            <dd class="col-sm-9"><?= esc(etiqueta_estado_usuario($usuario['estado'])) ?></dd>

            <dt class="col-sm-3">Fecha de alta</dt>
            <dd class="col-sm-9"><?= esc(formato_fecha($usuario['fecha_alta'] ?? null)) ?></dd>
        </dl>
        <div class="alert alert-info mb-0" role="status">
            El resumen de inmuebles y facturas se incorporará en la integración con esos módulos.
        </div>
    </div>
    <div class="card-footer">
        <a href="<?= route_to('admin.usuarios.index') ?>" class="btn btn-secondary">Volver al listado</a>
        <a
            href="<?= route_to('admin.usuarios.edit', $usuario['id_usuario']) ?>"
            class="btn btn-primary"
        >
            Modificar
        </a>

        <?php if (! $esUsuarioActual && $usuario['estado'] === 'activo') : ?>
            <form
                action="<?= route_to('admin.usuarios.block', $usuario['id_usuario']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning">Bloquear</button>
            </form>
        <?php endif ?>

        <?php if (! $esUsuarioActual && $usuario['estado'] !== 'baja') : ?>
            <form
                action="<?= route_to('admin.usuarios.deactivate', $usuario['id_usuario']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger">Dar de baja</button>
            </form>
        <?php endif ?>

        <?php if (in_array($usuario['estado'], ['bloqueado', 'baja'], true)) : ?>
            <form
                action="<?= route_to('admin.usuarios.activate', $usuario['id_usuario']) ?>"
                method="post"
                class="d-inline"
            >
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-success">Reactivar</button>
            </form>
        <?php endif ?>

        <form
            action="<?= route_to('admin.usuarios.reset-password', $usuario['id_usuario']) ?>"
            method="post"
            class="d-inline"
        >
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-info">Restablecer contraseña</button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
