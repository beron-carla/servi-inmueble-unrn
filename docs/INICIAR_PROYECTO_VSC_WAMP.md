# Iniciar ServiInmueble desde VS Code con WampServer

Guía para ejecutar ServiInmueble localmente en Windows con WampServer, PowerShell integrado de VS Code y el servidor de desarrollo de CodeIgniter.

## Requisitos

- WampServer instalado en `C:\wamp64`.
- PHP 8.2 o superior. Esta guía usa PHP `8.3.28` en `C:\wamp64\bin\php\php8.3.28`.
- Composer instalado.
- El proyecto en `C:\Proyectos\servi-inmueble-unrn`.
- La base `serviinmueble` existente en MySQL/MariaDB, con usuario `root`, contraseña vacía y puerto `3306`.

## Primera ejecución

### 1. Iniciar y verificar la base de datos

Iniciá WampServer y esperá a que su ícono indique que los servicios están activos. MySQL/MariaDB debe permanecer iniciado mientras uses la aplicación. En phpMyAdmin (`http://localhost/phpmyadmin`), verificá que exista `serviinmueble`.

La conexión de este entorno usa estos valores en `.env`:

```ini
database.default.hostname = localhost
database.default.database = serviinmueble
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

Como ya tenés creada la base, no hace falta crearla ni importar otra vez el archivo SQL. Si todavía no tiene las tablas del proyecto, seguí el paso 5 para ejecutar las migraciones.

### 2. Verificar PHP e `intl`

Abrí una terminal PowerShell integrada en VS Code y comprobá la versión de PHP que se usará desde la terminal:

```powershell
& "C:\wamp64\bin\php\php8.3.28\php.exe" -v
```

Para comprobar si la extensión `intl` está habilitada en ese PHP:

```powershell
& "C:\wamp64\bin\php\php8.3.28\php.exe" -m | Select-String -Pattern '^intl$'
```

Si aparece `intl`, está habilitada. También podés ver información detallada y qué archivo `php.ini` está cargando:

```powershell
& "C:\wamp64\bin\php\php8.3.28\php.exe" --ri intl
& "C:\wamp64\bin\php\php8.3.28\php.exe" --ini
```

Si `intl` no aparece o `--ri intl` informa que el módulo no existe:

1. Abrí el menú de WampServer y habilitá **PHP > PHP extensions > intl** para la versión `8.3.28`, si aparece en la lista.
2. Si no está en la lista, revisá el archivo `php.ini` indicado por `--ini` (normalmente `C:\wamp64\bin\php\php8.3.28\php.ini`) y verificá que la línea `extension=intl` esté habilitada, sin `;` al comienzo.
3. Cerrá y abrí de nuevo la terminal de VS Code y repetí la comprobación. Si también usás Apache de WampServer, reiniciá sus servicios.

CodeIgniter requiere `intl` y `mbstring`. Podés comprobar que Composer detecte los requisitos de PHP con:

```powershell
composer check-platform-reqs
```

### 3. Abrir la carpeta del proyecto

Abrí `C:\Proyectos\servi-inmueble-unrn` en VS Code y una terminal PowerShell integrada:

```powershell
Set-Location C:\Proyectos\servi-inmueble-unrn
```

### 4. Instalar dependencias y revisar `.env`

Instalá dependencias la primera vez o cuando cambien:

```powershell
composer install
```

Si PowerShell indica que `composer` no se reconoce, instalá Composer para Windows y volvé a abrir la terminal.

El archivo `.env` debe existir en la raíz del proyecto. Si todavía no existe, crealo a partir de la plantilla:

```powershell
Copy-Item env .env
```

Editá `.env` y confirmá los parámetros de base de datos indicados en el paso 1. Elegí la URL que corresponda al servidor que vas a usar:

Para el VirtualHost de Apache de WampServer:

```ini
app.baseURL = 'http://servi-inmueble.test/'
```

El nombre `servi-inmueble.test` debe estar configurado en Apache y resolver a `127.0.0.1` en el archivo `C:\Windows\System32\drivers\etc\hosts`. En la configuración de Apache, el `DocumentRoot` debe apuntar a la carpeta `public` del proyecto.

Para el servidor de desarrollo de CodeIgniter (`spark serve`):

```ini
app.baseURL = 'http://localhost:8080/'
```

Usá una sola URL base a la vez. Si cambiás entre Apache y Spark, actualizá `app.baseURL`, detené el servidor anterior e iniciá el que corresponda. En ambos casos WampServer debe mantener MySQL/MariaDB activo.

### 5. Crear o actualizar las tablas y los datos iniciales

Si la base todavía no tiene las tablas del proyecto, ejecutá estos comandos desde la carpeta del proyecto:

```powershell
& "C:\wamp64\bin\php\php8.3.28\php.exe" .\spark migrate --all
& "C:\wamp64\bin\php\php8.3.28\php.exe" .\spark db:seed DatabaseSeeder
```

No hace falta repetirlos en cada inicio. Si la base ya tiene las tablas y datos de ServiInmueble, no vuelvas a importar el SQL; ejecutá migraciones solo cuando el proyecto incorpore cambios de esquema.

El seeder crea el administrador inicial si todavía no existe:

- Correo: `admin@serviinmueble.local`
- Contraseña temporal: `Admin1234` //localmente como admin123 

En entorno de desarrollo también carga datos de demostración, incluido `propietario@serviinmueble.local` con contraseña `Propietario1234`.

//aunque yo utilizo de prueba manual a `pepe@gmail.com` con contraseña `pepepepe2`.

## Iniciar la aplicación

Con MySQL/MariaDB activo y `app.baseURL` configurada como `http://localhost:8080/`, iniciá el servidor de desarrollo de CodeIgniter:

```powershell
& "C:\wamp64\bin\php\php8.3.28\php.exe" .\spark serve
```

Dejá esa terminal abierta y visitá <http://localhost:8080>. Para detenerlo, presioná `Ctrl+C` en la terminal.

Con `spark serve` no necesitás iniciar Apache de WampServer; sí necesitás que MySQL/MariaDB esté activo. Si preferís usar Apache, iniciá ese servicio, configurá el VirtualHost para que apunte a la carpeta `public` del proyecto y usá la URL `http://servi-inmueble.test/`.

## Próximas ejecuciones

Cada vez que quieras trabajar en el proyecto con Spark:

1. Iniciá WampServer y verificá que MySQL/MariaDB esté activo.
2. Abrí una terminal en `C:\Proyectos\servi-inmueble-unrn`.
3. Confirmá que `app.baseURL` en `.env` sea `http://localhost:8080/`.
4. Iniciá CodeIgniter:

   ```powershell
   & "C:\wamp64\bin\php\php8.3.28\php.exe" .\spark serve
   ```

5. Abrí <http://localhost:8080>.

Si aparece `Unable to connect to the database`, verificá que MySQL/MariaDB esté activo, use el puerto `3306` y que `.env` tenga los parámetros correctos. Si PHP no encuentra `intl`, confirmá que VS Code esté usando el `php.ini` que revisaste con `--ini`.

## Cambios para desarrolladores que usan XAMPP

Al integrar estos cambios, no hace falta revertir código ni editar los formularios para que funcionen con XAMPP:

- Los formularios de login y cambio de contraseña, y el enlace del registro al login, ahora construyen sus URL con `site_url(route_to(...))`. Esto permite que respeten el `app.baseURL` configurado para cada entorno.
- El formulario de registro ya usaba ese mismo patrón.
- Los cambios de documentación no modifican la configuración de Apache, PHP ni la base de datos.

El archivo `.env` es local y está excluido de Git; no se comparte en el merge. Cada desarrollador debe conservar o crear su propio `.env` con su URL y credenciales locales. Para la configuración de XAMPP de la guía [Iniciar proyecto desde VS Code](./INICIAR_PROYECTO_VSC.md):

- Con Apache sirviendo el proyecto desde `htdocs`, `app.baseURL` puede ser `http://localhost/servi-inmueble/public/`.
- Con `spark serve`, usá `app.baseURL = 'http://localhost:8080/'` y mantené el servidor corriendo en la terminal.
- No uses `http://localhost/auth/login` salvo que Apache esté configurado con un VirtualHost cuyo `DocumentRoot` sea la carpeta `public` del proyecto.

No reemplaces `site_url(route_to(...))` por `route_to(...)` al hacer merge: las rutas relativas pueden resolver a una URL incorrecta según la URL base o la ubicación desde la que se envía el formulario.
