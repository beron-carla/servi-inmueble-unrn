# Configuración del entorno local — ServiInmueble

**Fecha:** 2026-11-08
**Proyecto:** ServiInmueble 2026 (CodeIgniter 4)
**Ruta del checkout verificado:** `C:\xampp\htdocs\servi-inmueble-unrn`

---

## 1. Objetivo

Dejar operativo el proyecto en local sobre XAMPP (Apache + MariaDB), cubriendo los requisitos que faltaban para el arranque: dependencias de Composer, extensiones PHP, variables de entorno y base de datos.

## 2. Entorno

| Componente | Versión |
|---|---|
| PHP CLI (XAMPP) | 8.2.12 observado en la validación |
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
| 6 | Base de datos | `serviinmueble` y `serviinmueble_test` tienen 11 tablas de aplicación y los 2 triggers canónicos |
| 7 | Verificación | `spark routes` y `spark db:table` ejecutan correctamente |
| 8 | Base de tests | `serviinmueble_test` (utf8mb4/utf8mb4_unicode_ci); HU-00 la migra y siembra con MySQL |
| 9 | `.env` ampliado | Agregados email (Mailpit), seeds y grupo `database.tests` (MySQL, no SQLite) |
| 10 | Script de control | Creado `scripts/check_env.php` + `scripts/check_env.bat` |
| 11 | Prompt de arranque | Creado `docs/prompts/HU-00_bootstrap.md` |
| 12 | Bootstrap HU-00 | Implementados configuración, 12 migraciones, 5 seeds, filtros, vistas, helpers, capas y 48 rutas explícitas |
| 13 | Pruebas | 18 tests / 80 assertions pasan contra `serviinmueble_test` |

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

Ejecutar desde `C:\xampp\htdocs\servi-inmueble-unrn` en CMD/PowerShell:

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
2. Abrir la URL de `app.baseURL` configurada en `.env` (en esta instalación: `http://localhost/servi-inmueble/public/`).

**Opción B — servidor embebido de CodeIgniter**

```bat
C:\xampp\php\php.exe spark serve
```

Requiere cambiar en `.env`:

```ini
app.baseURL = 'http://localhost:8080/'
```

Acceso: `http://localhost:8080/`

## 7. Verificación más reciente (2026-11-08)

Ejecución del script de control el **2026-11-08**:

```bat
C:\xampp\php\php.exe scripts\check_env.php
```

Resultado: **25 OK, 0 fallas, 1 advertencia** (exit code `0`).

| Bloque | Resultado |
|---|---|
| PHP y extensiones (`intl`, `mbstring`, `mysqli`, `openssl`, `json`, `curl`) | 7 OK; PHP CLI 8.2.12 |
| Directorios de `writable/` | 5 OK |
| Archivos base (`vendor/autoload.php`, `composer.lock`, `.env`, `spark`) | 4 OK |
| Variables de entorno (`app.baseURL`, BD de desarrollo, BD de tests, seed admin) | 4 OK |
| MariaDB (conexión, bases, 11 tablas de aplicación, 2 triggers) | 5 OK; la tabla `migrations` suma una tabla de metadatos |
| Mailpit (SMTP 1025) | 1 advertencia (opcional) |

Verificaciones adicionales:

- `spark routes` → 48 rutas explícitas del mapa de `docs/rutas.md`; no hay rutas automáticas (`autoRoute = false`).
- `spark migrate -g default` → creó las 11 tablas de aplicación y los 2 triggers en `serviinmueble`.
- `spark db:seed DatabaseSeeder` → dos ejecuciones completadas en desarrollo; la prueba de idempotencia confirma que no duplican datos.
- `spark migrate -g tests` → creó las 11 tablas de aplicación y los 2 triggers en `serviinmueble_test`.
- `spark db:seed DatabaseSeeder` → dos ejecuciones completadas en tests.
- `AdminUserSeeder` → en una prueba aislada, la primera ejecución mostró la contraseña temporal y la segunda no la repitió.
- `vendor\bin\phpunit` → `OK (18 tests, 80 assertions)` contra MySQL en `serviinmueble_test`.
- `scripts\check_env.php` → 25 OK, 0 fallas, 1 advertencia opcional (Mailpit).
- La versión efectiva del PHP CLI fue 8.2.12; la versión 8.3.36 consignada en el relevamiento inicial no quedó confirmada en esta ejecución.

> Las 11 tablas legadas de desarrollo se verificaron vacías y, con autorización, se reemplazaron mediante las migraciones canónicas; no se ejecutó `DROP DATABASE`. Mailpit no disponible es una advertencia opcional.

## 8. Consideraciones

- Reiniciar Apache tras modificar `php.ini`.
- La carpeta `writable/` debe tener permisos de escritura (por defecto correctos en Windows).
- Si MariaDB usa contraseña para `root`, actualizar `database.default.password` en `.env`.
- Para revertir el cambio de PHP: restaurar `C:\xampp\php\php.ini.bak-serviinmueble`.
- Desde WSL, `curl` puede fallar con `HTTP 000` aunque Apache esté levantado (escucha en `[::]:80`). Usar `curl.exe` de Windows o el navegador para probar la app.
- PHP no está en el `PATH` de WSL: invocar `/mnt/c/xampp/php/php.exe`.

## 9. Estado posterior al bootstrap

El código de HU-00 y sus tests están implementados. Para trabajar desde `C:\xampp\htdocs\servi-inmueble-unrn`:

1. Verificar configuración con `scripts\check_env.bat`; en la última ejecución terminó con 0 fallas y una advertencia opcional por Mailpit.
2. Ejecutar migraciones y seeds en la base de tests, no en la base de desarrollo ya importada:

   ```bat
   set "database.defaultGroup=tests"
   C:\xampp\php\php.exe spark migrate -g tests
   C:\xampp\php\php.exe spark db:seed DatabaseSeeder
   C:\xampp\php\php.exe spark db:seed DatabaseSeeder
   set "database.defaultGroup="
   ```

3. Ejecutar PHPUnit con `C:\xampp\php\php.exe vendor\bin\phpunit`.
4. HU-00 queda lista: BC-01 a BC-20 pasan, las bases usan el esquema canónico y el seed muestra la contraseña temporal una sola vez al crear el admin.

Documentos de referencia obligatoria:

- [`00_bootstrap.md`](00_bootstrap.md) — alcance, migraciones, seeds y BC-01 a BC-20.
- [`rutas.md`](rutas.md) — mapa canónico de rutas.
- [`decisiones.md`](decisiones.md) — esquema y decisiones D-01 a D-30.
- [`prompts/README.md`](prompts/README.md) — índice y orden de ejecución.
- [`bd/serviinmueble (1).sql`](<../bd/serviinmueble (1).sql>) — DDL canónico.

> Antes de HU-14/HU-15 hay que resolver **P-01** (registro de pagos). Ver `decisiones.md` §Pendientes. P-04 está resuelta; consultar [`00_bootstrap.md`](00_bootstrap.md) §8 para la matriz de aceptación.
