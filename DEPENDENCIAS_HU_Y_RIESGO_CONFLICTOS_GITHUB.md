# Dependencies Between User Stories and GitHub Conflict Risk

This document outlines the dependencies between HU-01 to HU-15, the files most likely to cause merge conflicts on GitHub, and recommendations for parallel work.

## 1) Dependency Map Between User Stories

| HU | Dependency | Notes |
|---|---|---|
| **HU-01 - User Registration** | None | Public authentication base. Creates Auth logic. |
| **HU-02 - Login** | HU-01 | Needs the `usuarios` table/model and authentication logic already in place. |
| **HU-03 - Logout** | HU-02 | Requires sessions/filters created by login. |
| **HU-04 - Create Users (Admin Only)** | HU-01/HU-02/HU-03 | Needs role system, `AuthFilter`/`RoleFilter`, and authentication working. |
| **HU-05 - Edit/Deactivate Users** | HU-04/HU-06 | Shares the same `admin/usuarios` module. |
| **HU-06 - List/Search Users** | HU-04 | Shares the same `admin/usuarios` module. |
| **HU-07 - Create Service Types** | HU-04 (roles) | Needs Admin permissions. Base catalog for Sprint 2. |
| **HU-08 - Edit/Deactivate Service Types** | HU-07/HU-09 | Shares the same `tipos_servicio` module. |
| **HU-09 - List Service Types** | HU-07 | Shares the same `tipos_servicio` module. |
| **HU-10 - Create Properties** | HU-01-06, HU-07-09 | Needs `usuarios` (owners), roles, and `tipos_servicio` to be present. |
| **HU-11 - Edit/Deactivate Properties (logical)** | HU-10/HU-12 | Shares the same `inmuebles` module. |
| **HU-12 - List/Search Properties** | HU-10 | Shares the same `inmuebles` module. |
| **HU-13 - Link Services to Property** | HU-10-HU-12 + HU-07-HU-09 | Creates `servicios_inmueble`. Uses `inmuebles` + `tipos_servicio`. |
| **HU-14 - Create Invoice** | HU-13 | Requires `servicios_inmueble` with status `Activo`. |
| **HU-15 - Invoice List with Totals** | HU-14 | Reads `facturas` already created. |

## 2) Files with Highest Conflict Risk

| File/Folder | Risk | Explanation |
|---|---|---|
| **`app/Config/Routes.php`** | **HIGH** | Almost all HUs add routes. Even if working on different groups, touching this file often causes conflicts. |
| **`app/Views/layout/sidebar.php`** | **HIGH** | Each module adds menu items. Multiple people editing this file at the same time almost always causes conflicts. |
| **`app/Controllers/Auth/*` (`AuthController.php`)** | **HIGH** | HU-01, HU-02, and HU-03 usually end up in the same `AuthController`. Splitting them is very likely to produce conflicts. |
| **`app/Controllers/Admin/UsuariosController.php` + `app/Views/admin/usuarios/*`** | **HIGH** | HU-04, HU-05, HU-06 share this entire module. Different methods, but same controller, views, and routes. |
| **`app/Controllers/Admin/TiposServicioController.php` + `app/Views/admin/tipos_servicio/*`** | **HIGH** | Same as Users. HU-07,08,09 are tightly coupled. |
| **`app/Controllers/InmueblesController.php` + `app/Views/inmuebles/*`** | **HIGH** | HU-10,11,12 share the same controller and views. |
| **`app/Controllers/ServiciosInmuebleController.php` + `app/Views/servicios_inmueble/*`** | **HIGH** | HU-13 creates/edits this controller. Touches shared flows. |
| **`app/Controllers/FacturasController.php` + `app/Views/facturas/*`** | **HIGH** | HU-14 and HU-15 share controller, routes, and views. HU-15 heavily reuses HU-14 logic. |
| **`app/Filters/*` (`AuthFilter`, `RoleFilter`)** | **MEDIUM-HIGH** | Touched less, but HU-02 and HU-04 often adjust auth/role logic. |
| **`app/Services/`, `app/Repositories/`, `app/Models/`** | **MEDIUM** | Less conflict-prone if files are created per HU. Risk increases if multiple people add methods to the same Service/Repository. |
| **`app/Database/Migrations/*`** | **MEDIUM-LOW** | Usually different files per HU. Main concern is maintaining correct chronological order for migrations. |

## 3) What to Parallelize and What to Keep Sequential

The key rule: **do not split the same CRUD across multiple people**.

### Sprint 1 - Authentication, Users & Catalogs

| Group | Recommendation | Reason |
|---|---|---|
| **HU-01, HU-02, HU-03 (Auth)** | **Sequential (same person)** | Share `AuthController`, `auth/*` routes, sessions, and filters. Do in order HU-01 -> HU-02 -> HU-03. |
| **HU-04, HU-05, HU-06 (Admin Users)** | **Sequential (same person)** | Same controller, views, and routes. High chance of conflicts if split. |
| **HU-07, HU-08, HU-09 (Service Types)** | **Sequential (same person)** | Same CRUD, same module. High chance of conflicts if split. |

**Recommendation for Sprint 1:** After merging Auth (01-03), you can safely parallelize **Users (04-06) vs Service Types (07-09)**, since they use different controllers, views, and folders. Even so, **`Routes.php` and `sidebar.php` will remain hot spots**.

### Sprint 2 - Properties, Service Linking & Invoicing

| Group | Recommendation | Reason |
|---|---|---|
| **HU-10, HU-11, HU-12 (Properties)** | **Sequential (same person)** | Same module. Needs consistent ownership checks across create/edit/list. |
| **HU-13 (Link Services to Property)** | **Individual** | Creates `servicios_inmueble`. Depends on HU-10-12 and HU-07-09 being stable. |
| **HU-14, HU-15 (Invoices)** | **Sequential (same person)** | Share `FacturasController`, routes, views, and totals logic. HU-15 reuses almost everything from HU-14. |

## 4) Practical Recommendations to Avoid Conflicts

| Action | Priority | Details |
|---|---|---|
| **Merge by complete module** | **CRITICAL** | Don't open PRs for partial work on the same module. Submit the full CRUD (controller+service+repo+views+routes) in a single PR. |
| **Minimize edits to shared files** | **CRITICAL** | Ideally, the person finishing a module adds its routes and sidebar entry in **their own PR** and merges it as soon as possible. If two people edit `Routes.php` or `sidebar.php`, conflicts are almost guaranteed. |
| **Work in different folders** | **HIGH** | Parallelize only when modules use completely different directories (`admin/usuarios/` vs `admin/tipos_servicio/`). |
| **Rebase frequently** | **HIGH** | If parallelizing Users vs Service Types, both should run `git pull --rebase origin/develop` at least once per day, especially before pushing. |
| **Create new files when possible** | **HIGH** | Adding new Services/Repositories/Migrations creates far fewer conflicts than modifying existing ones. |
| **Follow Sprint order** | **CRITICAL** | **Do not start HU-04-15 until HU-01-02-03 are merged into `develop`**. Auth, filters, roles, and base structure must be stable first. |

## Direct Recommendation

**Highest real risk files:** `app/Config/Routes.php`, `app/Views/layout/sidebar.php`, `AuthController`, `UsuariosController`, `TiposServicioController`, `InmueblesController`, `FacturasController`.

**Safest approach:**

1. **Phase 1:** HU-01 -> HU-02 -> HU-03 (sequential, same person)
2. **Phase 2:** After Phase 1 is merged, parallelize **HU-04-06** vs **HU-07-09** (different modules). Only assign to different people if each completes their **entire CRUD**.
3. **Phase 3:** After Phase 2 is merged, do **HU-10-12** (sequential, same person)
4. **Phase 4:** HU-13 (individual)
5. **Phase 5:** HU-14 -> HU-15 (sequential, same person)

**Pragmatic rule:** If the CRUD shares the same `Controller` + same `Routes` group + same `Views`, **do not parallelize it**. If they only share `Routes.php` or `sidebar.php`, parallelization is possible but requires constant rebasing.
