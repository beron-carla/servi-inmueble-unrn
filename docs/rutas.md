# Mapa de rutas · ServiInmueble 2026 · Hito 1

**Proyecto:** ServiInmueble 2026 (CodeIgniter 4.7.5)
**Fecha:** 2026-11-08
**Estado:** propuesto para implementar en el bootstrap (HU-00)
**Fuente de verdad:** este documento + [`docs/decisiones.md`](decisiones.md) + [`bd/serviinmueble (1).sql`](<../bd/serviinmueble (1).sql>)

> Regla: si una HU necesita una ruta que no está acá, **no se inventa**. Se agrega primero a este documento y se avisa al equipo.

---

## 1. Convenciones

- Rutas **explícitas**; `autoRoute = false` (ya está así en `app/Config/Routing.php`).
- URIs en español, kebab-case, sin prefijo `/api`.
- IDs numéricos con `(:num)`.
- Toda mutación es `POST` (no `GET`), con `csrf_field()` y patrón PRG.
- Nombres de ruta con `as` para usar `route_to()` / `site_url()` sin strings mágicos.
- Filtros: `auth`, `guest`, `role:admin`.
- Acceso denegado: **HTTP 403** con `app/Views/errors/html/error_403.php`; no redirigir.
- No hay rutas de recuperación de contraseña autoservicio en el Hito 1 (no está en las HU).
- No hay rutas de pago en el Hito 1 salvo que se apruebe P-01 (`docs/decisiones.md`).

---

## 2. Filtros

| Alias | Clase | Comportamiento |
|---|---|---|
| `auth` | `App\Filters\AuthFilter` | Sin sesión válida → redirect a `/auth/login`. Valida fila en `sesion`, `usuario.estado = 'activo'`, `cambiar_contrasenia = 0` y expiración de 30 min. |
| `guest` | `App\Filters\GuestFilter` | Con sesión válida → redirect a `/dashboard`. |
| `role:admin` | `App\Filters\RoleFilter` | Si `usuario.rol !== 'admin'` → 403 con vista amigable. |
| `csrf` | `CodeIgniter\Filters\CSRF` | Global en `before`; protege todos los POST. |

**Registro en `app/Config/Filters.php`:**

```php
public array $aliases = [
    // ... aliases nativos ...
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

---

## 3. Matriz de acceso

| Recurso | Visitante | Propietario | Admin |
|---|---|---|---|
| `/`, `/auth/login`, `/auth/registro` | ✅ | redirige a dashboard | redirige a dashboard |
| `/auth/logout`, `/auth/cambiar-contrasenia` | ❌ | ✅ | ✅ |
| `/dashboard` | ❌ | ✅ | ✅ |
| `/catalogo-servicios` | ❌ | ✅ (solo activos) | ✅ (puede ver todo) |
| `/inmuebles*` | ❌ | solo propios | todos |
| `/servicios-inmueble*` | ❌ | solo de inmuebles propios | todos |
| `/facturas*` | ❌ | solo de inmuebles propios | todas |
| `/admin/*` | ❌ | 403 | ✅ |

El control de propiedad se valida **en el servicio**, no solo en el filtro.

---

## 4. Mapa de rutas

### 4.1 Público y autenticación

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/` | `home` | `Home::index` | — | — | Redirige a login o dashboard |
| GET | `/auth/login` | `auth.login` | `AuthController::showLogin` | `guest` | HU-02 | CA-02.4 |
| POST | `/auth/login` | `auth.login.attempt` | `AuthController::login` | `guest` | HU-02 | CA-02.1 a CA-02.4 |
| GET | `/auth/registro` | `auth.register` | `AuthController::showRegister` | `guest` | HU-01 | CA-01.1 a CA-01.6 |
| POST | `/auth/registro` | `auth.register.store` | `AuthController::register` | `guest` | HU-01 | CA-01.1 a CA-01.6 |
| POST | `/auth/logout` | `auth.logout` | `AuthController::logout` | `auth` | HU-03 | CA-03.1 |
| GET | `/auth/cambiar-contrasenia` | `auth.password.edit` | `AuthController::showChangePassword` | `auth` | HU-02/HU-05 | CA-02.2, CA-05.8 |
| POST | `/auth/cambiar-contrasenia` | `auth.password.update` | `AuthController::changePassword` | `auth` | HU-02/HU-05 | CA-02.2, CA-05.8 |

### 4.2 Dashboard

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/dashboard` | `dashboard` | `DashboardController::index` | `auth` | HU-02/HU-03 | CA-02.4, H-24 |

### 4.3 Inmuebles (HU-10, HU-11, HU-12)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/inmuebles` | `inmuebles.index` | `InmueblesController::index` | `auth` | HU-12 | CA-12.1 a CA-12.4 |
| GET | `/inmuebles/nuevo` | `inmuebles.create` | `InmueblesController::create` | `auth` | HU-10 | CA-10.1 a CA-10.6 |
| POST | `/inmuebles` | `inmuebles.store` | `InmueblesController::store` | `auth` | HU-10 | CA-10.1 a CA-10.6 |
| GET | `/inmuebles/(:num)` | `inmuebles.show` | `InmueblesController::show/$1` | `auth` | HU-12 | CA-12.5 |
| GET | `/inmuebles/(:num)/editar` | `inmuebles.edit` | `InmueblesController::edit/$1` | `auth` | HU-11 | CA-11.1 a CA-11.6 |
| POST | `/inmuebles/(:num)` | `inmuebles.update` | `InmueblesController::update/$1` | `auth` | HU-11 | CA-11.1 a CA-11.6 |
| POST | `/inmuebles/(:num)/baja` | `inmuebles.deactivate` | `InmueblesController::deactivate/$1` | `auth` | HU-11 | CA-11.2, CA-11.3 |
| POST | `/inmuebles/(:num)/reactivar` | `inmuebles.activate` | `InmueblesController::activate/$1` | `auth` | HU-11 | CA-11.5 |

**Notas:**
- En `create`, si el usuario es admin, el formulario muestra un select de propietario (HU-10, alta por Admin). Si es propietario, se asigna a sí mismo.
- En `edit/update/deactivate/activate`, el servicio permite al propietario dueño o a un admin (CA-11.6).
- El estado `inactivo` no se elige en el alta; solo se llega por `baja` (CA-10.3 vs CA-11.2).

### 4.4 Servicios del inmueble (HU-13)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/inmuebles/(:num)/servicios/nuevo` | `servicios.create` | `ServiciosInmuebleController::create/$1` | `auth` | HU-13 | CA-13.1 a CA-13.9 |
| POST | `/inmuebles/(:num)/servicios` | `servicios.store` | `ServiciosInmuebleController::store/$1` | `auth` | HU-13 | CA-13.1 a CA-13.9 |
| GET | `/servicios-inmueble/(:num)` | `servicios.show` | `ServiciosInmuebleController::show/$1` | `auth` | HU-13 | CA-13.1 a CA-13.9 |
| GET | `/servicios-inmueble/(:num)/editar` | `servicios.edit` | `ServiciosInmuebleController::edit/$1` | `auth` | HU-13 | CA-13.2, CA-13.3 |
| POST | `/servicios-inmueble/(:num)` | `servicios.update` | `ServiciosInmuebleController::update/$1` | `auth` | HU-13 | CA-13.2, CA-13.3 |
| POST | `/servicios-inmueble/(:num)/estado` | `servicios.state` | `ServiciosInmuebleController::changeState/$1` | `auth` | HU-13 | CA-13.1, CA-13.7 |
| POST | `/servicios-inmueble/(:num)/baja` | `servicios.deactivate` | `ServiciosInmuebleController::deactivate/$1` | `auth` | HU-13 | CA-13.5, CA-13.7 |

**Notas:**
- `estado` recibe el nuevo estado por POST y valida la transición permitida (D-15).
- El alta exige inmueble activo, tipo activo y que no exista vínculo vigente (CA-13.4, CA-13.8, CA-13.9).

### 4.5 Facturas (HU-14, HU-15)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/facturas` | `facturas.index` | `FacturasController::index` | `auth` | HU-15 | CA-15.1 a CA-15.5 |
| GET | `/servicios-inmueble/(:num)/facturas/nuevo` | `facturas.create` | `FacturasController::create/$1` | `auth` | HU-14 | CA-14.1 a CA-14.6 |
| POST | `/servicios-inmueble/(:num)/facturas` | `facturas.store` | `FacturasController::store/$1` | `auth` | HU-14 | CA-14.1 a CA-14.6 |
| POST | `/facturas/(:num)/pagar` | `facturas.pay` | `FacturasController::pay/$1` | `auth` | HU-15 (soporte) | **P-01 pendiente** |

**Notas:**
- `pay` **solo se implementa si se aprueba P-01** (`docs/decisiones.md`). Si no, se elimina del mapa y HU-15 usa datos sembrados para “Total Pagado”.
- `facturas.index` acepta filtros por query string (ver sección 6).
- El alta solo se permite sobre servicios `activo` y propiedades no `inactivo` (CA-14.6).

### 4.6 Catálogo de servicios (HU-09, vista Propietario)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/catalogo-servicios` | `catalogo.servicios` | `CatalogoServiciosController::index` | `auth` | HU-09 | CA-09.4 |

**Notas:** solo muestra `tipo_servicio.activo = 1`, en modo lectura. El admin usa `/admin/tipos-servicio`.

### 4.7 Administración de usuarios (HU-04, HU-05, HU-06)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/admin/usuarios` | `admin.usuarios.index` | `Admin\UsuariosController::index` | `auth`, `role:admin` | HU-06 | CA-06.1 a CA-06.5 |
| GET | `/admin/usuarios/nuevo` | `admin.usuarios.create` | `Admin\UsuariosController::create` | `auth`, `role:admin` | HU-04 | CA-04.1 a CA-04.6 |
| POST | `/admin/usuarios` | `admin.usuarios.store` | `Admin\UsuariosController::store` | `auth`, `role:admin` | HU-04 | CA-04.1 a CA-04.6 |
| GET | `/admin/usuarios/(:num)/editar` | `admin.usuarios.edit` | `Admin\UsuariosController::edit/$1` | `auth`, `role:admin` | HU-05 | CA-05.1 a CA-05.8 |
| GET | `/admin/usuarios/(:num)` | `admin.usuarios.show` | `Admin\UsuariosController::show/$1` | `auth`, `role:admin` | HU-06 | CA-06.1 a CA-06.5 |
| POST | `/admin/usuarios/(:num)` | `admin.usuarios.update` | `Admin\UsuariosController::update/$1` | `auth`, `role:admin` | HU-05 | CA-05.1 a CA-05.8 |
| POST | `/admin/usuarios/(:num)/bloquear` | `admin.usuarios.block` | `Admin\UsuariosController::block/$1` | `auth`, `role:admin` | HU-05 | CA-05.2, CA-05.7 |
| POST | `/admin/usuarios/(:num)/baja` | `admin.usuarios.deactivate` | `Admin\UsuariosController::deactivate/$1` | `auth`, `role:admin` | HU-05 | CA-05.3, CA-05.7 |
| POST | `/admin/usuarios/(:num)/reactivar` | `admin.usuarios.activate` | `Admin\UsuariosController::activate/$1` | `auth`, `role:admin` | HU-05 | CA-05.2 |
| POST | `/admin/usuarios/(:num)/restablecer-contrasenia` | `admin.usuarios.reset-password` | `Admin\UsuariosController::resetPassword/$1` | `auth`, `role:admin` | HU-05 | CA-05.8 |

**Notas:**
- `block`, `deactivate` y `resetPassword` invalidan todas las sesiones del usuario (CA-05.7).
- Un admin no puede bloquearse, darse de baja ni quitarse el rol a sí mismo (CA-05.5).
- `resetPassword` genera la temporal, la muestra una vez y marca `cambiar_contrasenia = 1`.

### 4.8 Administración de tipos de servicio (HU-07, HU-08, HU-09)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/admin/tipos-servicio` | `admin.tipos.index` | `Admin\TiposServicioController::index` | `auth`, `role:admin` | HU-09 | CA-09.1 a CA-09.5 |
| GET | `/admin/tipos-servicio/nuevo` | `admin.tipos.create` | `Admin\TiposServicioController::create` | `auth`, `role:admin` | HU-07 | CA-07.1 a CA-07.6 |
| POST | `/admin/tipos-servicio` | `admin.tipos.store` | `Admin\TiposServicioController::store` | `auth`, `role:admin` | HU-07 | CA-07.1 a CA-07.6 |
| GET | `/admin/tipos-servicio/(:num)` | `admin.tipos.show` | `Admin\TiposServicioController::show/$1` | `auth`, `role:admin` | HU-09 | CA-09.5 |
| GET | `/admin/tipos-servicio/(:num)/editar` | `admin.tipos.edit` | `Admin\TiposServicioController::edit/$1` | `auth`, `role:admin` | HU-08 | CA-08.1 a CA-08.6 |
| POST | `/admin/tipos-servicio/(:num)` | `admin.tipos.update` | `Admin\TiposServicioController::update/$1` | `auth`, `role:admin` | HU-08 | CA-08.1 a CA-08.6 |
| POST | `/admin/tipos-servicio/(:num)/desactivar` | `admin.tipos.deactivate` | `Admin\TiposServicioController::deactivate/$1` | `auth`, `role:admin` | HU-08 | CA-08.1, CA-08.6 |
| POST | `/admin/tipos-servicio/(:num)/reactivar` | `admin.tipos.activate` | `Admin\TiposServicioController::activate/$1` | `auth`, `role:admin` | HU-08 | CA-08.6 |
| POST | `/admin/tipos-servicio/(:num)/eliminar` | `admin.tipos.destroy` | `Admin\TiposServicioController::destroy/$1` | `auth`, `role:admin` | HU-08 | CA-08.3 |

**Notas:**
- `destroy` solo procede si no hay `propiedad_servicio` asociados; si hay, responde con el aviso “No se puede eliminar: tiene servicios asociados” y ofrece desactivar (CA-08.3).
- La unicidad es `(tipo, proveedor_predeterminado)` (D-13).

### 4.9 Administración de inmuebles (HU-12, vista Admin)

| Método | URI | Nombre | Controlador::método | Filtros | HU | CA |
|---|---|---|---|---|---|---|
| GET | `/admin/inmuebles` | `admin.inmuebles.index` | `Admin\InmueblesController::index` | `auth`, `role:admin` | HU-12 | CA-12.4 |

**Notas:** listado global con filtros por propietario, ciudad, tipo y estado. La edición se hace por `/inmuebles/{id}/editar` (el servicio permite admin).

---

## 5. Código propuesto para `app/Config/Routes.php`

```php
<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Público
$routes->get('/', 'Home::index', ['as' => 'home']);

// Autenticación (invitados)
$routes->group('auth', ['filter' => 'guest'], static function (RouteCollection $routes): void {
    $routes->get('login', 'AuthController::showLogin', ['as' => 'auth.login']);
    $routes->post('login', 'AuthController::login', ['as' => 'auth.login.attempt']);
    $routes->get('registro', 'AuthController::showRegister', ['as' => 'auth.register']);
    $routes->post('registro', 'AuthController::register', ['as' => 'auth.register.store']);
});

// Autenticación (requiere sesión)
$routes->group('auth', ['filter' => 'auth'], static function (RouteCollection $routes): void {
    $routes->post('logout', 'AuthController::logout', ['as' => 'auth.logout']);
    $routes->get('cambiar-contrasenia', 'AuthController::showChangePassword', ['as' => 'auth.password.edit']);
    $routes->post('cambiar-contrasenia', 'AuthController::changePassword', ['as' => 'auth.password.update']);
});

// Dashboard
$routes->get('dashboard', 'DashboardController::index', ['filter' => 'auth', 'as' => 'dashboard']);

// Inmuebles
$routes->get('inmuebles', 'InmueblesController::index', ['filter' => 'auth', 'as' => 'inmuebles.index']);
$routes->get('inmuebles/nuevo', 'InmueblesController::create', ['filter' => 'auth', 'as' => 'inmuebles.create']);
$routes->post('inmuebles', 'InmueblesController::store', ['filter' => 'auth', 'as' => 'inmuebles.store']);
$routes->get('inmuebles/(:num)', 'InmueblesController::show/$1', ['filter' => 'auth', 'as' => 'inmuebles.show']);
$routes->get('inmuebles/(:num)/editar', 'InmueblesController::edit/$1', ['filter' => 'auth', 'as' => 'inmuebles.edit']);
$routes->post('inmuebles/(:num)', 'InmueblesController::update/$1', ['filter' => 'auth', 'as' => 'inmuebles.update']);
$routes->post('inmuebles/(:num)/baja', 'InmueblesController::deactivate/$1', ['filter' => 'auth', 'as' => 'inmuebles.deactivate']);
$routes->post('inmuebles/(:num)/reactivar', 'InmueblesController::activate/$1', ['filter' => 'auth', 'as' => 'inmuebles.activate']);

// Servicios del inmueble
$routes->get('inmuebles/(:num)/servicios/nuevo', 'ServiciosInmuebleController::create/$1', ['filter' => 'auth', 'as' => 'servicios.create']);
$routes->post('inmuebles/(:num)/servicios', 'ServiciosInmuebleController::store/$1', ['filter' => 'auth', 'as' => 'servicios.store']);
$routes->get('servicios-inmueble/(:num)', 'ServiciosInmuebleController::show/$1', ['filter' => 'auth', 'as' => 'servicios.show']);
$routes->get('servicios-inmueble/(:num)/editar', 'ServiciosInmuebleController::edit/$1', ['filter' => 'auth', 'as' => 'servicios.edit']);
$routes->post('servicios-inmueble/(:num)', 'ServiciosInmuebleController::update/$1', ['filter' => 'auth', 'as' => 'servicios.update']);
$routes->post('servicios-inmueble/(:num)/estado', 'ServiciosInmuebleController::changeState/$1', ['filter' => 'auth', 'as' => 'servicios.state']);
$routes->post('servicios-inmueble/(:num)/baja', 'ServiciosInmuebleController::deactivate/$1', ['filter' => 'auth', 'as' => 'servicios.deactivate']);

// Facturas
$routes->get('facturas', 'FacturasController::index', ['filter' => 'auth', 'as' => 'facturas.index']);
$routes->get('servicios-inmueble/(:num)/facturas/nuevo', 'FacturasController::create/$1', ['filter' => 'auth', 'as' => 'facturas.create']);
$routes->post('servicios-inmueble/(:num)/facturas', 'FacturasController::store/$1', ['filter' => 'auth', 'as' => 'facturas.store']);
// Solo si se aprueba P-01:
// $routes->post('facturas/(:num)/pagar', 'FacturasController::pay/$1', ['filter' => 'auth', 'as' => 'facturas.pay']);

// Catálogo para propietarios
$routes->get('catalogo-servicios', 'CatalogoServiciosController::index', ['filter' => 'auth', 'as' => 'catalogo.servicios']);

// Administración
$routes->group('admin', ['filter' => ['auth', 'role:admin']], static function (RouteCollection $routes): void {
    // Usuarios
    $routes->get('usuarios', 'Admin\UsuariosController::index', ['as' => 'admin.usuarios.index']);
    $routes->get('usuarios/nuevo', 'Admin\UsuariosController::create', ['as' => 'admin.usuarios.create']);
    $routes->post('usuarios', 'Admin\UsuariosController::store', ['as' => 'admin.usuarios.store']);
    $routes->get('usuarios/(:num)/editar', 'Admin\UsuariosController::edit/$1', ['as' => 'admin.usuarios.edit']);
    $routes->get('usuarios/(:num)', 'Admin\UsuariosController::show/$1', ['as' => 'admin.usuarios.show']);
    $routes->post('usuarios/(:num)', 'Admin\UsuariosController::update/$1', ['as' => 'admin.usuarios.update']);
    $routes->post('usuarios/(:num)/bloquear', 'Admin\UsuariosController::block/$1', ['as' => 'admin.usuarios.block']);
    $routes->post('usuarios/(:num)/baja', 'Admin\UsuariosController::deactivate/$1', ['as' => 'admin.usuarios.deactivate']);
    $routes->post('usuarios/(:num)/reactivar', 'Admin\UsuariosController::activate/$1', ['as' => 'admin.usuarios.activate']);
    $routes->post('usuarios/(:num)/restablecer-contrasenia', 'Admin\UsuariosController::resetPassword/$1', ['as' => 'admin.usuarios.reset-password']);

    // Tipos de servicio
    $routes->get('tipos-servicio', 'Admin\TiposServicioController::index', ['as' => 'admin.tipos.index']);
    $routes->get('tipos-servicio/nuevo', 'Admin\TiposServicioController::create', ['as' => 'admin.tipos.create']);
    $routes->post('tipos-servicio', 'Admin\TiposServicioController::store', ['as' => 'admin.tipos.store']);
    $routes->get('tipos-servicio/(:num)', 'Admin\TiposServicioController::show/$1', ['as' => 'admin.tipos.show']);
    $routes->get('tipos-servicio/(:num)/editar', 'Admin\TiposServicioController::edit/$1', ['as' => 'admin.tipos.edit']);
    $routes->post('tipos-servicio/(:num)', 'Admin\TiposServicioController::update/$1', ['as' => 'admin.tipos.update']);
    $routes->post('tipos-servicio/(:num)/desactivar', 'Admin\TiposServicioController::deactivate/$1', ['as' => 'admin.tipos.deactivate']);
    $routes->post('tipos-servicio/(:num)/reactivar', 'Admin\TiposServicioController::activate/$1', ['as' => 'admin.tipos.activate']);
    $routes->post('tipos-servicio/(:num)/eliminar', 'Admin\TiposServicioController::destroy/$1', ['as' => 'admin.tipos.destroy']);

    // Inmuebles (listado global)
    $routes->get('inmuebles', 'Admin\InmueblesController::index', ['as' => 'admin.inmuebles.index']);
});
```

> El orden importa: `nuevo` y `editar` se declaran antes de `(:num)` para que no los capture el comodín.

---

## 6. Parámetros de filtros (query string)

| Ruta | Parámetros | Reglas |
|---|---|---|
| `/admin/usuarios` | `q`, `rol`, `estado`, `page` | `q` busca parcial en `nombre_completo` y `mail`; `rol` ∈ {propietario, admin}; `estado` ∈ {activo, bloqueado, baja} |
| `/admin/tipos-servicio` | `categoria`, `activo`, `q`, `page` | `categoria` ∈ {publico, privado}; `activo` ∈ {0,1}; `q` busca en `tipo` y `proveedor_predeterminado` |
| `/inmuebles` | `ciudad`, `tipo`, `estado`, `q`, `page` | Por defecto oculta `inactivo`; `estado=inactivo` lo muestra explícitamente (CA-12.2) |
| `/admin/inmuebles` | `propietario`, `ciudad`, `tipo`, `estado`, `q`, `page` | `propietario` = `id_usuario`; por defecto oculta `inactivo` |
| `/facturas` | `inmueble`, `servicio`, `estado`, `desde`, `hasta`, `page` | `estado` ∈ {pendiente, pagada, vencida} (vencida es calculado); `desde`/`hasta` en `Y-m-d` sobre vencimiento aplicable |
| `/catalogo-servicios` | `categoria`, `q` | Solo `activo = 1` |

Validar siempre los parámetros en el servidor; ignorar valores inválidos en lugar de romper.

---

## 7. Controladores y archivos

| Controlador | Archivo | Responsabilidad |
|---|---|---|
| `Home` | `app/Controllers/Home.php` | Redirección de entrada |
| `AuthController` | `app/Controllers/AuthController.php` | Login, registro, logout, cambio de contraseña |
| `DashboardController` | `app/Controllers/DashboardController.php` | Panel posterior al login |
| `InmueblesController` | `app/Controllers/InmueblesController.php` | HU-10, HU-11, HU-12 |
| `ServiciosInmuebleController` | `app/Controllers/ServiciosInmuebleController.php` | HU-13 |
| `FacturasController` | `app/Controllers/FacturasController.php` | HU-14, HU-15 |
| `CatalogoServiciosController` | `app/Controllers/CatalogoServiciosController.php` | HU-09 (propietario) |
| `Admin\UsuariosController` | `app/Controllers/Admin/UsuariosController.php` | HU-04, HU-05, HU-06 |
| `Admin\TiposServicioController` | `app/Controllers/Admin/TiposServicioController.php` | HU-07, HU-08, HU-09 |
| `Admin\InmueblesController` | `app/Controllers/Admin/InmueblesController.php` | HU-12 (listado admin) |

**Servicios asociados (capa de negocio):**

| Servicio | Responsabilidad |
|---|---|
| `AuthService` | Login, registro, logout, cambio de contraseña, hash |
| `SesionService` | Crear/validar/invalidar filas de `sesion` |
| `LoginThrottleService` | Conteo de fallos en `historico_login` |
| `UsuarioService` | Alta, edición, bloqueo, baja, reactivación, reseteo, auditoría |
| `TipoServicioService` | Catálogo, unicidad, activación, borrado sin uso |
| `InmuebleService` | Alta/edición/baja, dirección, propiedad |
| `ServicioInmuebleService` | Vínculo, transiciones, unicidad vigente |
| `FacturaService` | Alta, período, snapshot, vencimientos, listado y totales |
| `EmailService` | Envío de bienvenida y notificaciones |
| `AuditoriaService` | Registro en `modificacion_usuario` |

---

## 8. Códigos de respuesta

| Situación | Código | Respuesta |
|---|---|---|
| Éxito de lectura | 200 | Vista |
| Mutación exitosa | 302 | Redirect + flash `success` |
| Validación fallida | 302 | Redirect back + `withInput()` + errores |
| Sin sesión | 302 | Redirect a `/auth/login` |
| Sin permiso | **403** | `error_403.php` |
| Recurso inexistente | 404 | `error_404.php` |
| CSRF inválido | 403 | Respuesta de CI4 (o vista 403 si se personaliza) |

No usar 200 para errores de permiso ni redirecciones silenciosas.

---

## 9. Rutas fuera de alcance

| Ruta | Motivo |
|---|---|
| `/auth/recuperar`, `/auth/reset/{token}` | No hay HU de recuperación autoservicio en el Hito 1 |
| `/api/*` | No hay SPA ni API en el Hito 1 |
| `/admin/reportes` | No hay HU de reportes |
| `/facturas/{id}/pagar` | Solo si se aprueba P-01 |
| `/perfil` | No hay HU de perfil propio; el cambio de contraseña está en `/auth/cambiar-contrasenia` |

---

## 10. Checklist de rutas

- [ ] `spark routes` muestra exactamente las rutas de este documento.
- [ ] `autoRoute = false`.
- [ ] Todas las rutas protegidas tienen `auth`.
- [ ] Todas las rutas `/admin/*` tienen `auth` + `role:admin`.
- [ ] Todos los POST tienen CSRF.
- [ ] Las rutas `nuevo`/`editar` están antes de `(:num)`.
- [ ] Existe `error_403.php` y se usa en `RoleFilter` y en los servicios.
- [ ] Los nombres de ruta coinciden con los usados en las vistas (`route_to`).
- [ ] No hay rutas sin HU asignada.
