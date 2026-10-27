---
name: ci4-routes-guard
description: Skill to structure and protect routes in app/Config/Routes.php using Filters, groups, and correct RBAC mapping for ServiInmueble.
---

# CI4 Routes Guard

## Goal

Create a clean, explicit routing map with proper authentication/authorization. Prevent unprotected admin routes and ensure consistency with all HUs.

## When to use

- Initial route setup
- Adding new modules
- Auditing route exposure
- Fixing permission gaps

## Route Groups

| Group | Prefix | Filter | Access |
|---|---|---|---|
| **Auth** | `/auth` | `guest` (optional) | Public: login, register, logout |
| **Dashboard** | `/` or `/dashboard` | `auth` | Authenticated users |
| **Inmuebles** | `/inmuebles` | `auth` | Authenticated (scoped to owner or admin based on action) |
| **Facturas** | `/facturas` | `auth` | Authenticated (scoped) |
| **Servicios** | `/servicios-inmueble` | `auth` | Authenticated (owner/admin) |
| **Admin** | `/admin` | `auth`, `role:administrador` | Admin only |

## Recommended Structure

```php
// app/Config/Routes.php
$routes->group('auth', ['namespace' => 'App\Controllers\Auth'], function($routes){
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attempt');
    $routes->get('registro', 'Auth::register');
    $routes->post('registro', 'Auth::store');
    $routes->get('logout', 'Auth::logout', ['filter' => 'auth']);
});

$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => ['auth','role:administrador']], function($routes){
    $routes->get('usuarios', 'Usuarios::index');
    $routes->get('usuarios/nuevo', 'Usuarios::create');
    $routes->post('usuarios/guardar', 'Usuarios::store');
    $routes->get('usuarios/editar/(:num)', 'Usuarios::edit/$1');
    $routes->post('usuarios/actualizar/(:num)', 'Usuarios::update/$1');
    $routes->post('usuarios/baja/(:num)', 'Usuarios::deactivate/$1');

    $routes->get('tipos-servicio', 'TiposServicio::index');
    $routes->get('tipos-servicio/nuevo', 'TiposServicio::create');
    $routes->post('tipos-servicio/guardar', 'TiposServicio::store');
    $routes->get('tipos-servicio/editar/(:num)', 'TiposServicio::edit/$1');
    $routes->post('tipos-servicio/actualizar/(:num)', 'TiposServicio::update/$1');
});

$routes->group('', ['filter' => 'auth'], function($routes){
    $routes->get('dashboard', 'Dashboard::index');
    $routes->get('inmuebles', 'Inmuebles::index');
    // etc
});
```

## Filters

| Filter | File | Purpose |
|---|---|---|
| **AuthFilter** | `app/Filters/AuthFilter.php` | Redirect to `/auth/login` if no session. |
| **RoleFilter** | `app/Filters/RoleFilter.php` | Check `session('rol_nombre')` or role id. Return 403 if not allowed. |
| **GuestFilter** | `app/Filters/GuestFilter.php` | Prevent logged-in users from accessing auth forms. |
| **CsrfFilter** | `app/Filters/CsrfFilter.php` | Enforce CSRF. |

Register in `app/Config/Filters.php` under `aliases`.

## Checklist

- [ ] All mutating routes protected by `auth`
- [ ] Admin routes protected by `auth` + `role:administrador`
- [ ] Owner-only resources checked in Service/Controller
- [ ] Auth routes handled correctly
- [ ] 403 page exists
- [ ] Routes match HU requirements
- [ ] No unprotected admin actions
