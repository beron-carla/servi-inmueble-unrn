# HU-00 · Bootstrap técnico · ServiInmueble 2026

**Proyecto:** ServiInmueble 2026 (CodeIgniter 4.7.5 · PHP 8.3.36 · MariaDB 10.11.16)
**Hito:** 1 (HU-01 a HU-15)
**Fecha:** 2026-11-08
**Estado:** listo para ejecutar antes de HU-01
**Depende de:** nada
**Habilita a:** HU-01 a HU-15

---

## 1. Objetivo

Dejar el proyecto en condiciones de implementar la primera HU sin resolver infraestructura, configuración o decisiones de esquema en el medio. Este documento **no implementa ninguna HU funcional**: prepara el terreno.

Al terminar el bootstrap deben existir:

- Migraciones y seeds reproducibles.
- Primer usuario administrador.
- CSRF, sesión de 30 minutos, filtros `auth`/`guest`/`role`.
- Layout de autenticación y vista 403.
- Configuración de email y template de bienvenida.
- Base de tests contra MySQL.
- Rutas base y estructura de capas (`Services`, `Repositories`, `Entities`).

---

## 2. Estado verificado del proyecto (2026-11-08)

| Componente | Estado | Evidencia |
|---|---|---|
| PHP | ✅ 8.3.36 (XAMPP) | `C:\xampp\php\php.exe -v` |
| CodeIgniter | ✅ 4.7.5 | `spark` |
| Composer | ✅ 2.11.1 + `vendor/` instalado | `composer.lock`, `vendor/` |
| Dependencias | ✅ `vendor/` presente | `composer install` ejecutado |
| `.env` | ✅ existe (ignorado por git) | `app.baseURL = http://localhost/servi-inmueble/public/` |
| Base de datos | ✅ `serviinmueble` importada, 11 tablas | `spark db:table` |
| Apache / mod_rewrite | ✅ XAMPP | `docs/CONFIGURACION_ENTORNO_LOCAL.md` |
| `intl` | ✅ activada | `php.ini` |
| Rutas | ⚠️ solo `GET /` | `spark routes` |
| Migraciones / seeds | ❌ vacías | `app/Database/Migrations`, `Seeds` |
| Primer admin | ❌ no existe | `usuario` vacío |
| Catálogos base | ❌ vacíos | `tipo_inmueble` y `tipo_servicio` sin filas |
| CSRF | ❌ deshabilitado | `app/Config/Filters.php` |
| Sesión | ⚠️ 7200 s | `app/Config/Session.php` |
| Email | ❌ sin configurar | `app/Config/Email.php` |
| Vista 403 | ❌ no existe | `app/Views/errors/html/` |
| Layout auth | ❌ no existe | `app/Views/layout/` |
| Filtros auth/role | ❌ no existen | `app/Filters/` |
| Tests | ⚠️ SQLite en memoria | `app/Config/Database.php` → `$tests` |

**Conclusión:** el entorno ya está operativo (esto cambió respecto de la auditoría anterior). Lo que falta es la capa de bootstrap: configuración, esquema reproducible, seeds, filtros, vistas base y rutas.

---

## 3. Cambios respecto de la auditoría anterior

Resuelto:

- `vendor/` instalado y `composer.lock` presente.
- `.env` creado con `baseURL` y credenciales de MySQL.
- Base `serviinmueble` importada con las 11 tablas.
- PHP 8.3.36 disponible vía XAMPP.
- `spark routes` y `spark db:table` funcionan.
- Esquema canónico decidido: `bd/serviinmueble (1).sql`.

Sigue pendiente:

- Migraciones y seeds.
- Primer admin.
- CSRF, sesión de 30 min, filtros.
- Layout auth, 403, email.
- Tests contra MySQL.
- Rutas y capas base.

---

## 4. Brechas que cubre este bootstrap

| # | Brecha | Prioridad | Se resuelve en |
|---|---|---|---|
| B-01 | No hay migraciones reproducibles | P0 | §6.5 |
| B-02 | No hay seeds ni primer admin | P0 | §6.6 |
| B-03 | CSRF deshabilitado | P0 | §6.1 |
| B-04 | Sesión de 7200 s | P0 | §6.1 |
| B-05 | No hay filtros `auth`/`guest`/`role` | P0 | §6.2 |
| B-06 | No hay vista 403 | P0 | §6.3 |
| B-07 | No hay layout de autenticación | P0 | §6.3 |
| B-08 | Email sin configurar ni template | P0 | §6.1, §6.3 |
| B-09 | Tests usan SQLite y el esquema es MySQL | P1 | §6.1, §6.9 |
| B-10 | Solo existe la ruta `/` | P0 | §6.8 |
| B-11 | No existen `Services`/`Repositories`/`Entities` | P1 | §6.7 |
| B-12 | No hay helpers de formato/rol | P2 | §6.4 |
| B-13 | `appTimezone`/locale en `en`/`UTC` | P2 | §6.1 |
| B-14 | No hay mapa de rutas | P0 | `docs/rutas.md` |
| B-15 | Skills describen un esquema obsoleto | P0 | `docs/decisiones.md` |

---

## 5. Alcance de HU-00

**Incluye:**

- Configuración de `App`, `Session`, `Filters`, `Email`, `Database` (tests).
- Filtros `AuthFilter`, `GuestFilter`, `RoleFilter`.
- Layout `auth`, vista `error_403`, template de email.
- Helpers `auth_helper`, `format_helper`.
- Migraciones de las 11 tablas + 2 triggers.
- Seeds: catálogos, admin, demo (solo desarrollo).
- Estructura de `Services`, `Repositories`, `Entities`, `Models`.
- `Routes.php` con el mapa de `docs/rutas.md`.
- Tests base y configuración de PHPUnit contra MySQL.
- Documentación de comandos y credenciales de seed.

**No incluye:**

- Lógica funcional de HU-01 a HU-15.
- Pantallas de login/registro/dashboard (se hacen en HU-01/HU-02).
- CRUD de usuarios, tipos de servicio, inmuebles, servicios o facturas.
- Pagos (P-01 pendiente).
- Recuperación de contraseña autoservicio.
- Reportes.

---

## 6. Tareas

### 6.1 Configuración

#### `app/Config/App.php`

```php
public string $baseURL = 'http://localhost/servi-inmueble/public/';
public string $indexPage = '';
public string $defaultLocale = 'es';
public array $supportedLocales = ['es'];
public string $appTimezone = 'America/Argentina/Buenos_Aires';
```

> `.env` ya define `app.baseURL`; se deja el mismo valor por defecto para que el proyecto funcione sin `.env` en un entorno de prueba.

#### `app/Config/Session.php`

```php
public int $expiration = 1800;          // 30 minutos
public bool $regenerateDestroy = true;  // anti fixation
public int $timeToUpdate = 300;
public bool $matchIP = false;           // evitar problemas con IPv6/proxy
```

#### `app/Config/Filters.php`

```php
public array $aliases = [
    // ... nativos ...
    'auth'  => \App\Filters\AuthFilter::class,
    'guest' => \App\Filters\GuestFilter::class,
    'role'  => \App\Filters\RoleFilter::class,
];

public array $globals = [
    'before' => [
        'csrf',
    ],
    'after' => [
        'secureheaders',
    ],
];
```

> Activar `csrf` global obliga a que **todos** los formularios POST usen `csrf_field()`. Es intencional.

#### `app/Config/Email.php`

```php
public string $fromEmail  = 'no-reply@serviinmueble.local';
public string $fromName   = 'ServiInmueble 2026';
public string $protocol   = 'smtp';
public string $SMTPHost   = 'localhost';
public int    $SMTPPort   = 1025;
public string $SMTPUser   = '';
public string $SMTPPass   = '';
public string $SMTPCrypto = '';
public string $mailType   = 'html';
```

Variables de entorno recomendadas:

```ini
email.fromEmail = no-reply@serviinmueble.local
email.fromName = ServiInmueble 2026
email.protocol = smtp
email.SMTPHost = localhost
email.SMTPPort = 1025
email.SMTPUser =
email.SMTPPass =
email.SMTPCrypto =
email.mailType = html
```

> En desarrollo usar **Mailpit** (`localhost:1025`, UI `http://localhost:8025`). En tests, inyectar un doble de `EmailService` que no envíe.

#### `app/Config/Database.php` → grupo `tests`

```php
public array $tests = [
    'DSN'      => '',
    'hostname' => '127.0.0.1',
    'username' => 'root',
    'password' => '',
    'database' => 'serviinmueble_test',
    'DBDriver' => 'MySQLi',
    'DBPrefix' => '',
    'DBDebug'  => true,
    'charset'  => 'utf8mb4',
    'DBCollat' => 'utf8mb4_unicode_ci',
    'port'     => 3306,
    'strictOn' => true,
    'failover' => [],
];
```

> **No usar SQLite.** El esquema usa `ENUM`, `CHECK`, `YEAR` y triggers.

#### `phpunit.dist.xml`

Descomentar/agregar:

```xml
<php>
    <env name="CI_ENVIRONMENT" value="testing"/>
    <env name="database.tests.hostname" value="127.0.0.1"/>
    <env name="database.tests.database" value="serviinmueble_test"/>
    <env name="database.tests.username" value="root"/>
    <env name="database.tests.password" value=""/>
    <env name="database.tests.DBDriver" value="MySQLi"/>
    <env name="database.tests.DBPrefix" value=""/>
    <env name="database.tests.charset" value="utf8mb4"/>
    <env name="database.tests.DBCollat" value="utf8mb4_unicode_ci"/>
    <env name="database.tests.port" value="3306"/>
</php>
```

#### Mensajes de validación en español

Crear `app/Language/es/Validation.php` con las traducciones de las reglas usadas (`required`, `valid_email`, `min_length`, `numeric`, `greater_than`, etc.) o definir los mensajes en cada `Validation` del servicio. **No** dejar mensajes en inglés visibles al usuario.

---

### 6.2 Filtros

Crear:

- `app/Filters/AuthFilter.php`
- `app/Filters/GuestFilter.php`
- `app/Filters/RoleFilter.php`

#### `AuthFilter` (pseudocódigo)

```php
public function before(RequestInterface $request, $arguments = null)
{
    $session = session();

    if (! $session->get('id_usuario')) {
        return redirect()->to('/auth/login');
    }

    $sesion = model(SesionModel::class)->find($session->get('id_sesion'));

    if (! $sesion || ! hash_equals($sesion['token'], (string) $session->get('token'))) {
        $session->destroy();
        return redirect()->to('/auth/login');
    }

    $inactivo = strtotime($sesion['ultima_actividad'] ?? $sesion['fecha_creacion']) < time() - 1800;
    if ($inactivo) {
        model(SesionModel::class)->delete($sesion['id_sesion']);
        $session->destroy();
        return redirect()->to('/auth/login');
    }

    $usuario = model(UsuarioModel::class)->find($session->get('id_usuario'));

    if (! $usuario || $usuario['estado'] !== 'activo') {
        model(SesionModel::class)->where('id_usuario', $session->get('id_usuario'))->delete();
        $session->destroy();
        return redirect()->to('/auth/login');
    }

    $ruta = trim($request->getUri()->getPath(), '/');
    if ((int) $usuario['cambiar_contrasenia'] === 1 && $ruta !== 'auth/cambiar-contrasenia') {
        return redirect()->to('/auth/cambiar-contrasenia');
    }

    // Actualizar ultima_actividad: cada request cuenta como actividad.
    model(SesionModel::class)->update($sesion['id_sesion'], ['ultima_actividad' => date('Y-m-d H:i:s')]);
}
```

#### `RoleFilter`

```php
public function before(RequestInterface $request, $arguments = null)
{
    $permitidos = array_map('strtolower', $arguments ?? []);
    $rol = strtolower((string) session('rol'));

    if (! in_array($rol, $permitidos, true)) {
        return service('response')
            ->setStatusCode(403)
            ->setBody(view('errors/html/error_403'));
    }
}
```

#### `GuestFilter`

```php
public function before(RequestInterface $request, $arguments = null)
{
    if (session('id_usuario')) {
        return redirect()->to('/dashboard');
    }
}
```

Registrar los aliases en `app/Config/Filters.php`.

---

### 6.3 Vistas base

#### `app/Views/layout/auth.php`

Layout limpio para login, registro y cambio de contraseña:

- `<!DOCTYPE html>`, `lang="es"`.
- CSS de AdminLTE + FontAwesome (mismos assets que `layout/main.php`).
- Contenedor `login-box` / `register-box`.
- Inclusión de `partials/alerts`.
- Secciones `content`, `styles`, `scripts`.
- Sin sidebar ni header autenticado.

#### `app/Views/errors/html/error_403.php`

- Mensaje: “No tiene permisos para esta acción”.
- Botón “Volver al dashboard” → `site_url('dashboard')`.
- Si no hay sesión, el botón puede ir a `site_url('auth/login')`.
- Debe devolver HTTP 403 (el status lo setea el filtro/controlador, no la vista).

#### `app/Views/emails/bienvenida.php`

- HTML simple con AdminLTE o CSS inline.
- Variables: `$nombre`, `$loginUrl`.
- Texto: bienvenida, usuario/email, enlace a login, política de contraseña.
- Versión texto plano opcional: `app/Views/emails/bienvenida_texto.php`.

---

### 6.4 Helpers

#### `app/Helpers/auth_helper.php`

```php
function usuario_actual(): ?array
function es_admin(): bool
function es_propietario(): bool
function puede_acceder_propiedad(int $idPropietario): bool
```

#### `app/Helpers/format_helper.php`

```php
function formato_moneda(float $monto): string
function formato_fecha(?string $fecha): string
function etiqueta_rol(string $rol): string
function etiqueta_estado_usuario(string $estado): string
function etiqueta_estado_propiedad(string $estado): string
function etiqueta_estado_servicio(string $estado): string
function etiqueta_tipo_servicio(string $tipo): string
function etiqueta_unidad(string $unidad): string
function etiqueta_tipo_inmueble(string $tipo): string
```

Cargar en `BaseController`:

```php
protected $helpers = ['form', 'url', 'auth', 'format'];
```

---

### 6.5 Migraciones

**Estrategia:** como el esquema usa `ENUM`, `CHECK`, `YEAR`, `DEFAULT CURRENT_TIMESTAMP` y triggers, las migraciones ejecutan el **DDL exacto** del SQL canónico con `$this->db->query()`. No se usa `Forge` para reconstruir el esquema, porque perdería fidelidad.

**Regla:** cada migración copia el `CREATE TABLE` correspondiente de `bd/serviinmueble (1).sql` (sin `DROP TABLE`, sin `CREATE DATABASE`, sin `USE`, sin `DELIMITER`).

Orden:

| # | Migración | Tabla / objeto |
|---|---|---|
| 1 | `2026-11-09-000001_CreateTipoInmueble` | `tipo_inmueble` |
| 2 | `2026-11-09-000002_CreateTipoServicio` | `tipo_servicio` |
| 3 | `2026-11-09-000003_CreateDireccion` | `direccion` |
| 4 | `2026-11-09-000004_CreateUsuario` | `usuario` |
| 5 | `2026-11-09-000005_CreateModificacionUsuario` | `modificacion_usuario` |
| 6 | `2026-11-09-000006_CreateSesion` | `sesion` |
| 7 | `2026-11-09-000007_CreatePropiedad` | `propiedad` |
| 8 | `2026-11-09-000008_CreatePropiedadServicio` | `propiedad_servicio` |
| 9 | `2026-11-09-000009_CreateFactura` | `factura` |
| 10 | `2026-11-09-000010_CreateTransaccion` | `transaccion` |
| 11 | `2026-11-09-000011_CreateHistoricoLogin` | `historico_login` |
| 12 | `2026-11-09-000012_CreateTipoServicioTriggers` | triggers `trg_tipo_servicio_unidad_bi` y `_bu` |

**Ejemplo de migración (tabla):**

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsuario extends Migration
{
    public function up(): void
    {
        $this->db->query(<<<'SQL'
CREATE TABLE `usuario` (
  `id_usuario` int NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(50) NOT NULL,
  `mail` varchar(100) NOT NULL,
  `contrasenia` varchar(255) NOT NULL,
  `rol` ENUM('propietario','admin') NOT NULL DEFAULT 'propietario',
  `estado` ENUM('activo','bloqueado','baja') NOT NULL DEFAULT 'activo',
  `fecha_alta` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cambiar_contrasenia` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `index_mail` (`mail`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);
    }

    public function down(): void
    {
        $this->db->query('DROP TABLE IF EXISTS `usuario`');
    }
}
```

**Ejemplo de migración (trigger):**

```php
public function up(): void
{
    $this->db->query(<<<'SQL'
CREATE TRIGGER `trg_tipo_servicio_unidad_bi`
BEFORE INSERT ON `tipo_servicio`
FOR EACH ROW
BEGIN
  IF NOT (
       (NEW.`tipo` = 'electricidad' AND NEW.`unidad_medida` IN ('kWh','Moneda fija'))
    OR (NEW.`tipo` = 'agua'         AND NEW.`unidad_medida` IN ('m³','Moneda fija'))
    -- ... resto de las combinaciones ...
  ) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Unidad de medida no compatible con el tipo de servicio';
  END IF;
END
SQL);
}
```

> No incluir `DELIMITER $$`; es una directiva del cliente MySQL, no SQL.

**Down:** eliminar triggers y tablas en orden inverso. `migrate:rollback` se encarga del orden si cada migración baja su propio objeto.

---

### 6.6 Seeds

| Seeder | Contenido | Entornos |
|---|---|---|
| `TipoInmuebleSeeder` | 5 tipos: casa, departamento, local comercial, oficina, galpón | todos |
| `TipoServicioSeeder` | Catálogo inicial de 9 tipos con proveedor de ejemplo | todos |
| `AdminUserSeeder` | Primer administrador | todos |
| `DemoSeeder` | Propietario demo, inmueble, servicio, facturas y transacción | development/testing |
| `DatabaseSeeder` | Orquesta los anteriores | todos |

#### `AdminUserSeeder`

```php
$email    = env('SEED_ADMIN_EMAIL', 'admin@serviinmueble.local');
$password = env('SEED_ADMIN_PASSWORD', 'Admin1234');

$existe = $this->db->table('usuario')->where('mail', strtolower($email))->countAllResults();
if ($existe) {
    return;
}

$hash = defined('PASSWORD_ARGON2ID')
    ? password_hash($password, PASSWORD_ARGON2ID)
    : password_hash($password, PASSWORD_BCRYPT);

$this->db->table('usuario')->insert([
    'nombre_completo'     => 'Administrador',
    'mail'                => strtolower($email),
    'contrasenia'         => $hash,
    'rol'                 => 'admin',
    'estado'              => 'activo',
    'cambiar_contrasenia' => 1,
]);
```

Credenciales por defecto (documentadas, **cambiar en producción**):

- Email: `admin@serviinmueble.local`
- Password: `Admin1234`
- Primer login: obliga a cambiarla.

#### `DemoSeeder` (solo development/testing)

- Propietario: `propietario@serviinmueble.local` / `Propietario1234`.
- Inmueble: “Depto Centro”, tipo `departamento`, estado `disponible`.
- Servicio: `electricidad` / Edenor, estado `activo`.
- Facturas: una `pagada` y dos `pendiente`, con vencimientos pasados y futuros.
- Transacción: pago total de la factura pagada.

> Este seeder es el que permite demostrar HU-15 mientras P-01 no se apruebe.

#### Idempotencia

Todos los seeders deben poder ejecutarse dos veces sin duplicar datos:

- Usuarios: buscar por `mail`.
- `tipo_inmueble`: buscar por `nombre`.
- `tipo_servicio`: buscar por `(tipo, proveedor_predeterminado)`.
- Demo: buscar por alias de propiedad y por período de factura.

---

### 6.7 Modelos, entidades, repositorios y servicios base

Crear la estructura (aunque las clases funcionales se completen en cada HU):

```text
app/
├── Entities/
│   ├── Usuario.php
│   ├── Propiedad.php
│   ├── TipoServicio.php
│   ├── PropiedadServicio.php
│   └── Factura.php
├── Models/
│   ├── UsuarioModel.php
│   ├── TipoInmuebleModel.php
│   ├── TipoServicioModel.php
│   ├── DireccionModel.php
│   ├── PropiedadModel.php
│   ├── PropiedadServicioModel.php
│   ├── FacturaModel.php
│   ├── TransaccionModel.php
│   ├── SesionModel.php
│   ├── HistoricoLoginModel.php
│   └── ModificacionUsuarioModel.php
├── Repositories/
│   ├── UsuarioRepository.php
│   ├── TipoServicioRepository.php
│   ├── PropiedadRepository.php
│   ├── PropiedadServicioRepository.php
│   └── FacturaRepository.php
└── Services/
    ├── AuthService.php
    ├── SesionService.php
    ├── LoginThrottleService.php
    ├── UsuarioService.php
    ├── TipoServicioService.php
    ├── InmuebleService.php
    ├── ServicioInmuebleService.php
    ├── FacturaService.php
    ├── EmailService.php
    └── AuditoriaService.php
```

**Reglas de los modelos:**

- `$table` y `$primaryKey` con los nombres del SQL.
- `$useTimestamps = false` (no hay `created_at`/`updated_at`).
- `$allowedFields` solo con columnas reales.
- `$returnType` puede ser `'array'` o una entidad; mantener consistencia.
- No poner lógica de negocio en modelos.

**Reglas de los repositorios:**

- Solo acceso a datos (Query Builder / Model).
- Sin validaciones de negocio.
- Sin HTML.

**Reglas de los servicios:**

- Validaciones, transiciones, unicidad, control de propiedad y auditoría.
- Transacciones de BD cuando se tocan varias tablas (`direccion` + `propiedad`, `factura` + `transaccion`).
- Devuelven arrays/entidades o lanzan excepciones de dominio; no imprimen.

---

### 6.8 Rutas

Copiar el bloque de `docs/rutas.md` §5 en `app/Config/Routes.php`.

Verificar:

```bat
C:\xampp\php\php.exe spark routes
```

Debe listar todas las rutas del mapa y **ninguna** ruta automática.

---

### 6.9 Tests

- `serviinmueble_test` ya está creada y vacía. Verificar con `scripts\check_env.bat`.
- Usar `DatabaseTestTrait` con `$refresh = true`, `$namespace = 'App'` y `$seed = 'DatabaseSeeder'` en los tests de integración.
- Tests de bootstrap (no dependen de HU-01):
  - Configuración: `App`, `Session` (1800), `Filters` (aliases y CSRF global), `Email`, `Database::$tests` (MySQLi).
  - Migraciones: 11 tablas y 2 triggers en `serviinmueble_test`.
  - Seeds: admin con `cambiar_contrasenia = 1` y hash verificable; catálogos; idempotencia.
  - `SesionService`: invalidación por usuario (logout, bloqueo y baja).
  - `RoleFilter`: 403 con la vista amigable.
  - Trigger de `tipo_servicio`: rechaza una unidad incompatible.
  - `EmailService`: doble que no envía.
- Los tests de flujo HTTP (login, dashboard, 403 de `/admin`) se agregan en HU-01/HU-02, cuando existan los controladores.

**No** testear contra SQLite.

---

### 6.10 Documentación

- `docs/rutas.md` → mapa de rutas (este repo).
- `docs/decisiones.md` → esquema y decisiones.
- `docs/PROMPT_GENERAL.md` → prompt unificado.
- `docs/CONFIGURACION_ENTORNO_LOCAL.md` → ya existe; actualizar si cambia algo.
- Actualizar `agents/servi-inmueble-ci4/SKILL.md` §6 o dejar una nota que remita a `docs/decisiones.md`.
- Actualizar `env` con las variables nuevas (email, seeds, tests).

---

## 7. Comandos (Windows / XAMPP)

Ejecutar desde `C:\xampp\htdocs\servi-inmueble`.

### 7.1 Crear la base de tests

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS serviinmueble_test DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 7.2 Migrar y sembrar

```bat
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark db:seed DatabaseSeeder
```

Si la base `serviinmueble` ya fue importada desde el SQL y las migraciones chocan con tablas existentes, reiniciar la base de desarrollo:

```bat
C:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS serviinmueble; CREATE DATABASE serviinmueble DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\php\php.exe spark migrate
C:\xampp\php\php.exe spark db:seed DatabaseSeeder
```

> El SQL canónico sigue siendo la referencia. Las migraciones deben producir un esquema equivalente.

### 7.3 Verificar

```bat
C:\xampp\php\php.exe spark routes
C:\xampp\php\php.exe spark db:table
```

### 7.4 Tests

```bat
C:\xampp\php\php.exe vendor\bin\phpunit
```

### 7.5 Desde WSL

```bash
/mnt/c/xampp/php/php.exe spark routes
/mnt/c/xampp/php/php.exe vendor/bin/phpunit
```

### 7.6 Mailpit (desarrollo)

1. Descargar Mailpit para Windows.
2. Ejecutar `mailpit.exe`.
3. SMTP: `localhost:1025`; UI: `http://localhost:8025`.

---

## 8. Criterios de aceptación del bootstrap

- [ ] **BC-01** `spark routes` lista todas las rutas de `docs/rutas.md` y ninguna automática.
- [ ] **BC-02** `spark migrate` crea las 11 tablas y los 2 triggers en una base vacía.
- [ ] **BC-03** `spark db:seed DatabaseSeeder` crea catálogos y admin; se puede ejecutar dos veces sin duplicar.
- [ ] **BC-04** El seed crea un admin `activo`, con hash verificable (`password_verify`) y `cambiar_contrasenia = 1`.
- [ ] **BC-05** La configuración global incluye `csrf` y un test verifica que un POST sin token recibe 403.
- [ ] **BC-06** `Session::$expiration = 1800` y `AuthFilter` invalida sesiones con `ultima_actividad` mayor a 30 min (test unitario).
- [ ] **BC-07** `SesionService` invalida filas por usuario (logout, bloqueo y baja) en un test unitario.
- [ ] **BC-08** `RoleFilter` devuelve 403 para un rol no autorizado en un test unitario.
- [ ] **BC-09** `error_403.php` existe y muestra “Volver al dashboard”.
- [ ] **BC-10** `layout/auth.php` existe y renderiza una vista de prueba.
- [ ] **BC-11** `EmailService` se prueba con un doble (o Mailpit) sin depender de un controlador.
- [ ] **BC-12** `phpunit` corre contra `serviinmueble_test` y los tests de bootstrap pasan.
- [ ] **BC-13** No hay referencias a las tablas/columnas del esquema viejo (`roles`, `usuarios`, `inmuebles`, `servicios_inmueble`, `facturas`, `id_rol`, columna `password_hash`) en el código nuevo.
- [ ] **BC-14** `appTimezone` y locale están en español/Argentina.
- [ ] **BC-15** `env` documenta las variables nuevas.
- [ ] **BC-16** `docs/rutas.md`, `docs/decisiones.md` y `docs/PROMPT_GENERAL.md` están enlazados desde el README o desde el prompt.
- [ ] **BC-17** `BaseController` carga los helpers `form`, `url`, `auth`, `format`.
- [ ] **BC-18** Los modelos usan `$useTimestamps = false`.
- [ ] **BC-19** Los seeds de demo solo corren fuera de producción.
- [ ] **BC-20** El primer login del admin no requiere email (la contraseña temporal se muestra una vez).

---

## 9. Riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|---|---|---|
| Migraciones no reproducen exactamente el SQL | Tests y producción divergen | Copiar el DDL textual; comparar con `SHOW CREATE TABLE` |
| Triggers no se crean en migración | Se pierde la validación de unidad | Migración 12 con `CREATE TRIGGER`; test que intente una combinación inválida |
| CSRF global rompe formularios existentes | 403 en POST | Agregar `csrf_field()` en todas las vistas; test de POST sin token |
| Sesión de 30 min mal implementada | CA-02.2 falla | `expiration=1800` + chequeo de `ultima_actividad` en `AuthFilter` |
| Email no configurado | HU-01 no envía bienvenida | Mailpit en desarrollo; doble en tests; `EmailService` desacoplado |
| Tests con SQLite | Falsos positivos | `$tests` MySQLi + base `serviinmueble_test` |
| Skills con esquema viejo | Código inconsistente | `docs/decisiones.md` + actualizar skill principal |
| `tipo_servicio` ENUM vs HU-07 | No se puede guardar “Energía Eléctrica” | D-12/D-13; validar con el equipo |
| P-01 sin aprobar | HU-15 no demuestra pagos reales | `DemoSeeder` + decisión pendiente |

---

## 10. Definición de terminado (HU-00)

1. Todos los BC en verde.
2. `spark migrate` y `spark db:seed` corren desde cero sin errores.
3. `spark routes` coincide con `docs/rutas.md`.
4. `phpunit` pasa los tests de humo.
5. El admin seed puede entrar, cambiar la contraseña y llegar a `/dashboard`.
6. Un propietario recibe 403 en `/admin/*`.
7. Un POST sin CSRF recibe 403.
8. El correo de bienvenida se ve en Mailpit.
9. No quedan referencias al esquema viejo.
10. `docs/decisiones.md`, `docs/rutas.md` y `docs/PROMPT_GENERAL.md` están commiteados.

---

## 11. Próximo paso

Con HU-00 cerrado, ejecutar en este orden:

```
HU-01 → HU-02 → HU-03 → HU-04 → HU-06 → HU-05 → HU-07 → HU-09 → HU-08
→ HU-10 → HU-12 → HU-11 → HU-13 → HU-14 → HU-15
```

Cada HU se implementa con `docs/PROMPT_GENERAL.md` + su `HU-XX_compacto.md` + `docs/rutas.md` + `docs/decisiones.md`.
