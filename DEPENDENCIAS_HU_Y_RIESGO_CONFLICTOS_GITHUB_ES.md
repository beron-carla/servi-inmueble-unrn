# Dependencias entre Historias de Usuario y Riesgo de Conflictos en GitHub

Este documento explica qué historias de usuario dependen entre sí, qué archivos son más propensos a generar conflictos al hacer merge y cómo organizar el trabajo para evitar líos entre desarrolladores.

## 1) Mapa de dependencias entre HU

| HU | Dependencia | Explicación |
|---|---|---|
| **HU-01 - Registro de usuarios** | Ninguna | Punto de partida. Crea la lógica de autenticación pública. |
| **HU-02 - Inicio de sesión** | HU-01 | Necesita la tabla/modelo `usuarios` y la lógica de registro ya creada. |
| **HU-03 - Cierre de sesión** | HU-02 | Requiere que las sesiones y filtros creados en el login estén funcionando. |
| **HU-04 - Alta de usuarios (solo Admin)** | HU-01, HU-02, HU-03 | Necesita el sistema de roles, los filtros `AuthFilter`/`RoleFilter` y la autenticación funcionando. |
| **HU-05 - Modificación y baja de usuarios** | HU-04, HU-06 | Comparte el mismo módulo `admin/usuarios`. |
| **HU-06 - Consulta y listado de usuarios** | HU-04 | Comparte el mismo módulo `admin/usuarios`. |
| **HU-07 - Alta de tipos de servicio** | HU-04 (roles) | Necesita permisos de administrador. Es el catálogo base para el Sprint 2. |
| **HU-08 - Modificación y baja de tipos de servicio** | HU-07, HU-09 | Comparte el mismo módulo `admin/tipos_servicio`. |
| **HU-09 - Consulta y listado de tipos de servicio** | HU-07 | Comparte el mismo módulo `admin/tipos_servicio`. |
| **HU-10 - Alta de inmuebles** | HU-01 - HU-06, HU-07 - HU-09 | Necesita usuarios (propietarios), roles y los tipos de servicio ya existentes. |
| **HU-11 - Modificación y baja lógica de inmuebles** | HU-10, HU-12 | Comparte el mismo módulo `inmuebles`. |
| **HU-12 - Consulta y listado de inmuebles** | HU-10 | Comparte the same module `inmuebles` (shares the same module `inmuebles`). |
| **HU-13 - Vinculación de servicios a un inmueble** | HU-10 - HU-12, HU-07 - HU-09 | Crea la tabla `servicios_inmueble`. Utiliza `inmuebles` y `tipos_servicio. |
| **HU-14 - Alta de factura** | HU-13 | Necesita que exista `servicios_inmueble` con estado `Activo`. |
| **HU-15 - Listado de facturas con totales** | HU-14 | Lee las facturas creadas en HU-14. |
| 

## 2) Archivos con mayor riesgo de conflicto

Estos son los archivos que, con mayor probabilidad, van a generar conflictos si varias personas los editan al mismo tiempo.

| Archivo / Carpeta | Riesgo | Explicación |
|---|---|---|
| **`app/Config/Routes.php`** | **ALTO** | Casi todas las HU añaden rutas. Aunque sean módulos distintos, tocar este archivo suele generar conflictos. |
| **`app/Views/layout/sidebar.php`** | **ALTO** | Cada módulo añade su opción al menú lateral. Si dos personas lo modifican a la vez, es muy común que haya conflicto. |
| **`app/Controllers/Auth/AuthController.php`** | **ALTO** | HU-01, HU-02 y HU-03 suelen ir todas en el mismo controlador. Si se dividen entre personas, hay casi seguro un conflicto. |
| **`app/Controllers/Admin/UsuariosController.php` + `app/Views/admin/usuarios/*`** | **ALTO** | HU-04, HU-05 y HU-06 comparten todo el módulo. Aunque sean métodos distintos, el controlador, las vistas y las rutas son los mismos. |
| **`app/Controllers/Admin/TiposServicioController.php` + `app/Views/admin/tipos_servicio/*`** | **ALTO** | Igual que el módulo de usuarios. CRUD completamente compartido. |
| **`app/Controllers/InmueblesController.php` + `app/Views/inmuebles/*`** | **ALTO** | HU-10, HU-11 y HU-12 comparten controlador, rutas y vistas. |
| **`app/Controllers/ServiciosInmuebleController.php` + `app/Views/servicios_inmueble/*`** | **ALTO** | HU-13 suele crear o modificar todo este controlador. |
| **`app/Controllers/FacturasController.php` + `app/Views/facturas/*`** | **ALTO** | HU-14 y HU-15 comparten controlador, routes, vistas y lógica de totales. |
| **`app/Filters/AuthFilter.php`, `app/Filters/RoleFilter.php`** | **MEDIO-ALTO** | Se tocan con menos frecuencia, pero HU-02 y HU-04 pueden ajustar su lógica. |
| **`app/Services/`, `app/Repositories/`, `app/Models/`** | **MEDIO** | Menor riesgo si se crean archivos nuevos por HU. El riesgo aumenta si varios desarrolladores añaden métodos al mismo Service o Repository. |
| **`app/Database/Migrations/*`** | **MEDIO-BAJO** | Suele haber archivos distintos por HU. Hay que tener cuidado con el orden cronológico para que las migraciones se ejecuten correctamente. |

## 3) ¿Qué se puede paralelizar y qué debe ir en orden?

La regla de oro: **No dividas el mismo CRUD entre varias personas**.

### Sprint 1 - Autenticación, Usuarios y Catálogos

| Agrupación | Recomendación | Razón |
|---|---|---|
| **HU-01, HU-02, HU-03 (Autenticación)** | **Secuencial - misma persona** | Comparten `AuthController`, rutas `auth/*`, sesiones y filtros. Hay que hacerlos en orden: HU-01 → HU-02 → HU-03. |
| **HU-04, HU-05, HU-06 (Usuarios - Admin)** | **Secuencial - misma persona** | Mismo controlador, mismas vistas, mismas rutas. Si se dividen, es casi seguro que haya conflictos. |
| **HU-07, HU-08, HU-09 (Tipos de Servicio)** | **Secuencial - misma persona** | Igual que usuarios. Es un CRUD completo y está muy acoplado entre sí. |

**Truco:** Tras mergear HU-01 a HU-03 a `develop`, **sí se puede paralelizar** HU-04-06 y HU-07-09, ya que son módulos con carpetas, controladores y vistas completamente distintos. Aún así, `Routes.php` y `sidebar.php` seguirán siendo puntos calientes.

### Sprint 2 - Inmuebles, Vinculación de Servicios y Facturación

| Agrupación | Recomendación | Razón |
|---|---|---|
| **HU-10, HU-11, HU-12 (Inmuebles)** | **Secuencial - misma persona** | Comparten controlador, rutas, vistas. Además, hay que mantener la coherencia en el control de propiedad. |
| **HU-13 (Vinculación servicios-inmueble)** | **Individual** | Crea `servicios_inmueble` la# PR Title
En este repositorio hay un proyecto web llamado "ServiInmueble 2026" desarrollado con CodeIgniter 4, PHP 8.2+, MySQL/MariaDB y AdminLTE 3/4. El objetivo es gestionar propiedades, propietarios, tipos de servicio, vinculación de servicios a inmuebles, facturas y usuarios/roles.

Necesito que realices la **HU-01: "Registro de nuevos usuarios"** siguiendo estrictamente el `PROMPT GENERAL` que aparece en `PROMPTS_POR_HISTORIA_DE_USUARIO_HITO1.txt`. 

Antes de hacer nada, debes:
1. Leer SIEMPRE primero: `agents/servi-inmueble-ci4/SKILL.md`
2. Tener en cuenta el resto de skills ubicados in `agents/`: `ci4-module-scaffold`, `ci4-routes-guard`, `ci4-migration-builder`, `adminlte-view-ccraft`, `ci4-frontend-ui, `ci4-security-audit, `ci4-hu-validator, `ci4-test-suite`
3. Seguir estrictamente: `Controller -> Service -> Repository -> Model/Entity`
4. La lógica de negocio, validaciones complejas, transiciones de estado and CONTROL DE PROPIEDAD must always go in the layer Service.
5. Controller ONLY must handle HTTP, call Service, show flash messages and redirect with PRG pattern.
6. Repository ONLY must access data with Query Builder/Model of CI4.
7. NO use business logic in Controllers or Models/Entities.
8. Use CodeIgniter 4.x, PHP 8.2+, MySQL/MariaDB, AdminLTE 3/4.
9. No SPAs (NO React, Vue, Angular). Classic forms.
10. No physical deletion. Use logical deletion with states or soft-delete.
11. Control of property mandatory: A Owner ONLY accesses their own properties, services_inmueble and invoices.
12. All access to protected routes must pass through filters auth/role.
13. Use password_hash() / password_verify(). Regenerate session at login. Destroy session at logout.
14. Use CSRF on all POST forms with `<?= csrf_field() ?>`.
15. User messages in SPANISH.
16. Follow the conventions of the major skill at all times.

Alcance para HU-01:
- Ruta pública: `/auth/registro`
- Campos: nombre, email, password
- Rol por defecto: Propietario
- Estado inicial: Activo
- Contraseña hasheada with password_hash()
- Email único (case-insensitive)
- Mínimo 8 caracteres, al menos una letra and a número
- Todos los campos obligatorios
- Guardar solo hash
- Redirigir con "Registro exitoso" tras éxito

Sigue el flujo por módulo: `ci4-migration-builder` -> `ci4-routes-guard` -> `ci4-module-scaffold` -> `adminlte-view-craft` -> `ci4-frontend-ui` -> `ci4-security-audit` -> `ci4-hu-validator` -> `ci4-test-suite`
Al terminar, ejecuta `ci4-hu-validator` comparar lo implementado contra HU-01 and its Criterios de Aceptación. Correge todo lo que quede marcado as FALTANTE or PARCIAL. 

Dale, implementalo, take your time. 

### Problem statement recap
In this repository there is a web project called "ServiInmueble 2026" developed with CodeIgniter 4, PHP 8.2+, MySQL/MariaDB and AdminLTE 3/4. The goal is to manage properties, owners, service types, linking services to properties, invoices and users/roles.

I need you to perform the **HU-01: "Registro de nuevos usuarios"** following strictly the `PROMPT GENERAL` that appears in `PROMPTS_POR_HISTORIA_DE_USUARIO_HITO1.txt`. 

Before doing anything, you must:
1. Read ALWAYS first: `agents/servi-inmueble-ci4/SKILL.md`
2. Take into account the rest of skills located in `agents/`: `ci4-module-scaffold`, `ci4-routes-guard`, `ci4-migration-builder`, `adminlte-view-craft`, `ci4-frontend-ui`, `ci4-security-audit`, `ci4-hu-validator`, `ci4-test-suite`
3. Follow strictly: `Controller -> Service -> Repository -> Model/Entity`
4. Business logic, complex validations, state transitions and PROPERTY CONTROL must always go in the Service layer.
5. Controller ONLY must handle HTTP, call Service, show flash messages and redirect with PRG pattern.
6. Repository ONLY must access data with Query Builder/Model of CI4.
7. NO use business logic in Controllers or Models/Entities.
8. Use CodeIgniter 4.x, PHP 8.2+, MySQL/MariaDB, AdminLTE 3/4.
9. No SPAs (NO React, Vue, Angular). Classic forms.
10. No physical deletion. Use logical deletion with states or soft-delete.
11. Control of property mandatory: A Owner ONLY accesses their own properties, services_inmueble and invoices.
12. All access to protected routes must pass through filters auth/role.
13. Use password_hash() / password_verify(). Regenerate session at login. Destroy session at logout.
14. Use CSRF on all POST forms with `<?= csrf_field() ?>`.
15. User messages in SPANISH.
16. Follow the conventions of the major skill at all times.

Scope for HU-01:
- Public route: `/auth/registro`
- Fields: nombre, email, password
- Default role: Propietario
- Initial status: Activo
- Password hashed with password_hash()
- Email unique (case-insensitive)
- Minimum 8 characters, at least one letter and one number
- All fields required
- Save only hash
- Redirect with "Registro exitoso" after success

Follow module flow: `ci4-migration-builder` -> `ci4-routes-guard` -> `ci4-module-scaffold` -> `adminlte-view-craft` -> `ci4-frontend-ui` -> `ci4-security-audit` -> `ci4-hu-validator` -> `ci4-test-suite`
At the end, run `ci4-hu-validator` to compare implemented vs HU-01 and its Acceptance Criteria. Fix everything marked as MISSING or PARTIAL.

Go ahead, implement it, take your time.

### Answer
I'll start by reading the required skills and understanding the project structure (migrations, models, routes, auth) before implementing HU-01.

Let me read the major skill and related files.