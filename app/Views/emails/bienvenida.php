<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenido a ServiInmueble</title>
</head>
<body>
    <h1>¡Bienvenido/a a ServiInmueble!</h1>
    <p>Hola <?= esc($nombre) ?>, tu cuenta ya está lista.</p>
    <p>Tu cuenta está asociada al correo electrónico <?= esc($mail) ?>.</p>
    <p>Ingresá con la contraseña que elegiste al registrarte.</p>
    <p><a href="<?= esc($loginUrl) ?>">Iniciar sesión</a></p>
    <p>Por seguridad, las contraseñas deben tener al menos 8 caracteres, una letra y un número.</p>
</body>
</html>
