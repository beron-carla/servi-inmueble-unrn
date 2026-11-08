<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Acceso denegado</title>
</head>
<body>
    <main>
        <h1>403 - Acceso denegado</h1>
        <p>No tiene permisos para esta acción.</p>
        <?php $destino = session()->get('id_usuario') ? 'dashboard' : 'auth/login'; ?>
        <a href="<?= site_url($destino) ?>">Volver al dashboard</a>
    </main>
</body>
</html>
