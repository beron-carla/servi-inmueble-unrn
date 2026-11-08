# Configuración del entorno local — ServiInmueble

**Fecha:** 2026-11-08
**Proyecto:** ServiInmueble 2026 (CodeIgniter 4)
**Ruta:** `C:\xampp\htdocs\servi-inmueble`

---

## 1. Objetivo

Dejar operativo el proyecto en local sobre XAMPP (Apache + MariaDB), cubriendo los requisitos que faltaban para el arranque: dependencias de Composer, extensiones PHP, variables de entorno y base de datos.

## 2. Entorno

| Componente | Versión |
|---|---|
| PHP (XAMPP) | 8.3.36 |
| CodeIgniter | 4.7.5 |
| MariaDB | 10.11.16 |
| Composer | 2.11.1 |
| Apache | XAMPP (mod_rewrite habilitado) |

## 3. Cambios realizados

| # | Acción | Detalle |
|---|---|---|
| 1 | Instalación de Composer | `composer.phar` descargado en la raíz del proyecto |
| 2 | Instalación de dependencias | `composer install` → generó `vendor/` y `composer.lock` |
| 3 | Extensión `intl` | Activada en `C:\xampp\php\php.ini` (`;extension=intl` → `extension=intl`) |
| 4 | Backup de configuración PHP | `C:\xampp\php\php.ini.bak-serviinmueble` |
| 5 | Archivo `.env` | Creado desde la plantilla `env` con parámetros de app y BD |
| 6 | Base de datos | Importado `bd/serviinmueble (1).sql` → BD `serviinmueble` (11 tablas + 2 triggers) |
| 7 | Verificación | `spark routes` y `spark db:table` ejecutan correctamente |
| 8 | Base de tests | Creada `serviinmueble_test` (utf8mb4/utf8mb4_unicode_ci), vacía para migraciones de PHPUnit |
| 9 | `.env` ampliado | Agregados email (Mailpit), seeds y grupo `database.tests` (MySQL, no SQLite) |
| 10 | Script de control | Creado `scripts/check_env.php` + `scripts/check_env.bat` |
| 11 | Prompt de arranque | Creado `docs/prompts/HU-00_bootstrap.md` |

> `intl` es requisito obligatorio de CodeIgniter 4 (`ext-intl` en `composer.json`); sin ella el arranque y la instalación fallan.

## 4. Configuración aplicada (`.env`)

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost/servi-inmueble/public/'

database.default.hostname = localhost
database.default.database = serviinmueble
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.DBPrefix =
database.default.port = 3306

# Email de desarrollo (Mailpit)
email.fromEmail = no-reply@serviinmueble.local
email.fromName = 'ServiInmueble 2026'
email.protocol = smtp
email.SMTPHost = localhost
email.SMTPPort = 1025
email.SMTPUser =
email.SMTPPass =
email.SMTPCrypto =
email.mailType = html

# Seeds
SEED_ADMIN_EMAIL = admin@serviinmueble.local
SEED_ADMIN_PASSWORD = Admin1234

# Tests (MySQL, no SQLite)
database.tests.hostname = 127.0.0.1
database.tests.database = serviinmueble_test
database.tests.username = root
database.tests.password =
database.tests.DBDriver = MySQLi
database.tests.DBPrefix =
database.tests.charset = utf8mb4
database.tests.DBCollat = utf8mb4_unicode_ci
database.tests.port = 3306
```

Notas:

- `baseURL` debe apuntar a la carpeta que contiene `index.php` (`public/`), de lo contrario los assets de AdminLTE no cargan.
- `root` sin contraseña corresponde a la configuración por defecto de XAMPP.
- `.env` y `vendor/` están excluidos por `.gitignore`.
- `serviinmueble_test` debe existir antes de correr PHPUnit. No se importa el SQL: las migraciones de HU-00 crean el esquema en la base de tests.
- Mailpit es opcional. Sin Mailpit, la app funciona; solo no se pueden ver los emails en `http://localhost:8025`.

## 5. Procedimiento reproducible

Ejecutar desde `C:\xampp\htdocs\servi-inmueble` en CMD/PowerShell:

```bat
REM 1. Dependencias (si no existe composer.phar)
C:\xampp\php\php.exe -r "copy('https://getcomposer.org/installer','composer-setup.php');"
C:\xampp\php\php.exe composer-setup.php --install-dir=. --filename=composer.phar
C:\xampp\php\php.exe composer.phar install

REM 2. Variables de entorno
copy env .env
REM editar .env según la sección 4

REM 3. Base de datos
C:\xampp\mysql\bin\mysql.exe -u root < "bd\serviinmueble (1).sql"

REM 4. Verificación
C:\xampp\php\php.exe spark routes
C:\xampp\php\php.exe spark db:table
```

## 6. Ejecución

**Opción A — Apache (configuración actual)**

1. Iniciar **Apache** y **MariaDB** desde el panel de XAMPP.
2. Abrir: `http://localhost/servi-inmueble/public/`

**Opción B — servidor embebido de CodeIgniter**

```bat
C:\xampp\php\php.exe spark serve
```

Requiere cambiar en `.env`:

```ini
app.baseURL = 'http://localhost:8080/'
```

Acceso: `http://localhost:8080/`

## 7. Verificación realizada

Ejecución del script de control el **2026-11-08**:

```bat
C:\xampp\php\php.exe scripts\check_env.php
```

Resultado: **25 OK, 0 fallas, 1 advertencia** (exit code `0`).

| Bloque | Resultado |
|---|---|
| PHP y extensiones (`intl`, `mbstring`, `mysqli`, `openssl`, `json`, `curl`) | 7 OK |
| Directorios de `writable/` | 5 OK |
| Archivos base (`vendor/autoload.php`, `composer.lock`, `.env`, `spark`) | 4 OK |
| Variables de entorno (`app.baseURL`, BD de desarrollo, BD de tests, seed admin) | 4 OK |
| MariaDB (conexión, bases, 11 tablas, 2 triggers) | 5 OK |
| Mailpit (SMTP 1025) | 1 advertencia (opcional) |

Verificaciones adicionales:

- `spark routes` → lista únicamente `GET /` → `\App\Controllers\Home::index`. No hay rutas automáticas (`autoRoute = false`).
- `spark migrate:status` → “No migrations were found.” (aún no existen migraciones; las crea HU-00).
- `spark db:table` → confirma conexión a `localhost / serviinmueble / root / MySQLi / 3306`.
- La importación SQL finalizó sin errores y creó las 11 tablas del esquema (`usuario`, `propiedad`, `factura`, `transaccion`, etc.) más los 2 triggers de `tipo_servicio`.
- `serviinmueble_test` existe, está vacía y tiene `utf8mb4_unicode_ci`.
- Apache responde `HTTP 200` en `http://localhost/servi-inmueble/public/` (probado con `curl.exe` de Windows).

> La advertencia de Mailpit no bloquea el arranque: solo afecta la prueba manual del envío de emails. Si se necesita, iniciar Mailpit y volver a correr el script.

## 8. Consideraciones

- Reiniciar Apache tras modificar `php.ini`.
- La carpeta `writable/` debe tener permisos de escritura (por defecto correctos en Windows).
- Si MariaDB usa contraseña para `root`, actualizar `database.default.password` en `.env`.
- Para revertir el cambio de PHP: restaurar `C:\xampp\php\php.ini.bak-serviinmueble`.
- Desde WSL, `curl` puede fallar con `HTTP 000` aunque Apache esté levantado (escucha en `[::]:80`). Usar `curl.exe` de Windows o el navegador para probar la app.
- PHP no está en el `PATH` de WSL: invocar `/mnt/c/xampp/php/php.exe`.

## 9. Punto de partida para HU-00

El entorno está listo. El arranque se hace así:

1. Verificar que no haya regresiones:

   ```bat
   scripts\check_env.bat
   ```

   Debe terminar con **0 fallas**.

2. Abrir la sesión de trabajo en `C:\xampp\htdocs\servi-inmueble`.

3. Pegar el **prompt general** de [`PROMPT_GENERAL.md`](PROMPT_GENERAL.md) §2.

4. Pegar el prompt de [`docs/prompts/HU-00_bootstrap.md`](prompts/HU-00_bootstrap.md) §2.

Estado esperado del repo antes de HU-00:

| Elemento | Estado |
|---|---|
| `system/` (framework CI4 4.7.5) | presente en la raíz |
| `vendor/` + `composer.lock` | instalados |
| `app/Config/Routes.php` | solo `GET /` |
| `app/Database/Migrations/` | vacío |
| `app/Database/Seeds/` | vacío |
| `app/Filters/` | sin filtros propios |
| `app/Views/layout/auth.php` | no existe |
| `app/Views/errors/html/error_403.php` | no existe |
| BD `serviinmueble` | 11 tablas + 2 triggers, sin datos |
| BD `serviinmueble_test` | vacía, lista para migraciones |

Documentos de referencia obligatoria:

- [`00_bootstrap.md`](00_bootstrap.md) — alcance, migraciones, seeds y BC-01 a BC-20.
- [`rutas.md`](rutas.md) — mapa canónico de rutas.
- [`decisiones.md`](decisiones.md) — esquema y decisiones D-01 a D-30.
- [`prompts/README.md`](prompts/README.md) — índice y orden de ejecución.
- [`bd/serviinmueble (1).sql`](<../bd/serviinmueble (1).sql>) — DDL canónico.

> Antes de HU-14/HU-15 hay que resolver **P-01** (registro de pagos). Ver `decisiones.md` §Pendientes.
