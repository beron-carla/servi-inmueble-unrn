---
name: servi-inmueble-ci4
description: Major skill for the ServiInmueble 2026 project built with CodeIgniter 4 (PHP 8.2+), MySQL/MariaDB, AdminLTE, and a Services/Repositories layered approach with full coverage of Hito 1 user stories.
---

# ServiInmueble 2026 - CodeIgniter 4 Development Skill (Major)

## 1) Role

Act as an expert full-stack developer specialized in **CodeIgniter 4**. Your goal is to build the "ServiInmueble 2026" web application in a secure, consistent, and maintainable way, strictly following the Hito tag1 requirements and the conventions defined in this skill.

## 2) Principles

- **Use native CI4 first:** Prioritize built-in features such as Routes, Filters, Validation, Migrations, Seeds, Sessions, Helpers, and Configuration.
- **Pragmatic with third-party tools:** The use of Composer packages and UI libraries (e.g. **AdminLTE 3/4**, Bootstrap, FontAwesome) is allowed if they speed up development without turning the project into a SPA.
- **Separation of concerns:** Keep HTTP logic, business rules, and data access clearly separated.
- **Server-side first:** All critical validations, authorization checks, and state transitions must be enforced on the server.
- **Preserve history:** Never perform physical deletes for entities that require traceability (users, properties, services, invoices). Use soft deletes/states instead.
- **Minimal and explicit:** Keep code readable, avoid magic, and follow the project conventions at all times.

## 3) Architecture

This project uses an adapted layered architecture tailored to CodeIgniter 4. The objective is to decouple business logic from Controllers and Models.

### 3.1 Layer Diagram

```text
┌─────────────────────────────────────────────┘
└─────────────────────────────────────────────┘
┌─────────────────────────────────────────────┐
│               FRONTEND (VIEWS)               │
│     AdminLTE / Bootstrap / HTML5 / CSS3 / JS │
└──────────────────┬──────────────────────────┘
                    │  HTTP Requests
                    ▼
┌─────────────────────────────────────────────┐
│                FILTERS / ROUTES              │
│        Auth, Role, CSRF, Throttling          │
└──────────────────┬──────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────┐
│               CONTROLLER LAYER               │
│  app/Controllers/ - HTTP handling only       │
│  Parse request, call Service, render/redirect│
└──────────────────┬──────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────┐
│                SERVICE LAYER                 │
│  app/Services/ - Business rules & validation │
│  Orchestrates logic between Repositories    │
└──────────────────┬──────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────┐
│              REPOSITORY LAYER                │
│  app/Repositories/ - Data access only        │
│  Use CI4 Models/Query Builder with prepared  │
└──────────────────┬──────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────┐
│                MODEL / ENTITY LAYER          │
│  app/Models/ & app/Entities/ - Mapping/POPOs │
│  Table mapping, casts and domain structure   │
└─────────────────────────────────────────────┘
```

### 3.2 Layer Responsibilities

| Layer | Path | Responsibility |
|---|---|---|
| **Routes** | `app/Config/Routes.php` | Define explicit routes, group by module and protect with Filters. |
| **Filters** | `app/Filters/` | Cross-cutting logic: authentication, role-based authorization, CSRF, brute-force protection. |
| **Controller** | `app/Controllers/` | HTTP entry point only. Parse `$_GET`/`$_POST`, delegate to the corresponding Service, handle flash messages, apply PRG and render views. |
| **Service** | `app/Services/` | Business rules, state transitions, uniqueness checks, ownership checks, and complex validations. Must never directly output HTML. |
| **Repository** | `app/Repositories/` | All database access. Use CI4 Models or Query Builder with parameter binding/prepared statements. Return Entities or normalized arrays. |
| **Model/Entity** | `app/Models/`, `app/Entities/` | Schema mapping, allowed fields, casts, and simple accessors. Keep business logic out of these. |
| **View** | `app/Views/` | Presentation layer using AdminLTE with `<?= $this->extend('layout/main') ?>` and `<?= $this->section('content') ?>`. |

## 4) Technology Stack

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 8.2+ | Server-side language |
| **CodeIgniter 4** | 4.x | Core framework (MVC, Routes, Filters, Validation, Sessions, Migrations, Seeds) |
| **MySQL / MariaDB** | 5.7+ / 10.4+ | Relational database |
| **Composer** | Latest | Autoloading and dependency management |
| **HTML5, CSS3, JavaScript** | Standard | Markup, styling and client-side behavior |
| **AdminLTE** | 3.x / 4.x | Responsive admin UI for forms, tables, dashboards and alerts |
| **Bootstrap & FontAwesome** | Compatible | UI components and icons |

> Do not use SPA frameworks (React, Vue, Angular, Svelte). Standard form submissions are preferred. `fetch()` may be used only for small, justified interactions.

## 5) Project Structure

```text
servi-inmueble/
├── app/
│   ├── Config/
│   │   ├── Routes.php
│   │   ├── Filters.php
│   │   ├── Database.php
│   │   └── Validation.php
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   ├── Inmuebles/
│   │   ├── ServiciosInmueble/
│   │   └── Facturas/
│   ├── Database/
│   │   ├── Migrations/
│   │   └── Seeds/
│   ├── Entities/
│   ├── Filters/
│   ├── Helpers/
│   ├── Libraries/
│   ├── Models/
│   ├── Repositories/
│   ├── Services/
│   └── Views/
│       ├── layout/
│       │   ├── main.php
│       │   ├── header.php
│       │   └── sidebar.php
│       ├── auth/
│       ├── admin/usuarios/
│       ├── admin/tipos-servicio/
│       ├── inmuebles/
│       ├── servicios-inmueble/
│       └── facturas/
├── public/
│   ├── assets/
│   │   ├── css/
│   │   ├── js/
│   │   ├── img/
│   │   └── adminlte/
│   └── index.php
├── spark
└── writable/
```

## 6) Database Design

### 6.1 Required Tables

| Table | Purpose |
|---|---|
| `roles` | User roles (`Administrador`, `Propietario`) |
| `usuarios` | System users with authentication and status |
| `tipos_servicio` | Master catalog of services |
| `inmuebles` | Properties linked to their owner |
| `servicios_inmueble` | Link between properties and service types with contract data |
| `facturas` | Invoices with consumption, due dates, amounts and payment info |

### 6.2 Key Fields

- `roles`: `id`, `nombre` (UNIQUE), `descripcion`, `created_at`, `updated_at`
- `usuarios`: `id`, `nombre`, `email` (UNIQUE, case-insensitive), `password_hash`, `id_rol`, `estado` (`Activo\|Bloqueado\|Baja`), `intentos_fallidos`, `bloqueado_hasta`, `ultimo_login`, `created_at`, `updated_at`, `deleted_at`
- `tipos_servicio`: `id`, `nombre` (UNIQUE), `categoria` (`Público\|Privado`), `unidad_medida` (`kWh\|m³\|Gigas\|Moneda fija`), `proveedor_predeterminado`, `activo` (TINYINT/BOOLEAN), `created_at`, `updated_at`
- `inmuebles`: `id`, `id_usuario_propietario` (FK to `usuarios`), `alias`, `calle`, `numero`, `piso`, `depto`, `ciudad`, `tipo` (`Casa\|Departamento\|Local Comercial\|Oficina\|Galpón`), `superficie` (DECIMAL), `estado` (`Ocupado\|Disponible\|En Mantenimiento\|Inactivo`), `created_at`, `updated_at`, `deleted_at`
- `servicios_inmueble`: `id`, `id_inmueble` (FK), `id_tipo_servicio` (FK), `cpe_pagar`, `numero_cuenta`, `numero_medidor`, `domicilio_facturacion`, `titular`, `estado` (`Activo\|Pausado\|En Revisión\|Inactivo`), `created_at`, `updated_at`
- `facturas`: `id`, `id_servicio_inmueble` (FK), `periodo`, `fecha_emision`, `fecha_vto1`, `fecha_vto2`, `monto_total` (DECIMAL(10,2)), `consumo`, `lectura_anterior`, `lectura_actual`, `estado` (`Pendiente\|Pagada\|Vencida`), `fecha_pago`, `monto_abonado`, `medio_pago`, `nro_comprobante`, `observaciones`, `created_at`, `updated_at`

### 6.3 Constraints & Rules

- All foreign keys must have correct references.
- `UNIQUE(usuarios.email)`, `UNIQUE(roles.nombre)`, `UNIQUE(tipos_servicio.nombre)`.
- Prevent duplicate active link: `UNIQUE(id_inmueble, id_tipo_servicio)` for active `servicios_inmueble` records when applicable.
- Prevent duplicate invoice: `UNIQUE(id_servicio_inmueble, periodo)`.
- Use soft deletes for `usuarios`, `inmuebles`. For `tipos_servicio` prefer `activo = 0` when linked to history.
- For `servicios_inmueble` and `facturas`, never physically delete records once created. Use `estado = Inactivo`.
- Add proper indexes on foreign keys and frequently filtered columns (`estado`, `id_usuario_propietario`, `id_rol`).
- Use `ENGINE=InnoDB`, `utf8mb4_unicode_ci`.

## 7) Security Guidelines

| Rule | Implementation |
|---|---|
| **Sessions** | Use CodeIgniter 4 Sessions. Call `session()->regenerate(true)` on successful login. Fully destroy session and cookie on logout. |
| **Password hashing** | Use `password_hash()` with `PASSWORD_ARGON2ID` (preferred) or `PASSWORD_BCRYPT`. Never store or log plain-text passwords. Verify with `password_verify()`. |
| **Authentication** | Unauthenticated users must be redirected to `/auth/login`. |
| **RBAC** | Enforce via `AuthFilter` and `RoleFilter`. Valid roles: `Administrador`, `Propietario`. Unauthorized access must return/view `403` with message: `"No tiene permisos para esta acción"`. |
| **Brute force protection** | Track failed login attempts by email. After 5 failed attempts block for 15 minutes. Always return generic message: `"Email o contraseña incorrectos"`. |
| **Account states** | Users with `Bloqueado` or `Baja` cannot log in. |
| **CSRF protection** | Use `<?= csrf_field() ?>` in all mutating forms. Keep CSRF enabled. |
| **Input validation** | Validate all inputs server-side with CI4 Validation. Apply `trim()` and cast where needed. |
| **PRG Pattern** | After any `POST` that mutates data, redirect to a safe route with flash messages (`success`/`error`). |
| **Ownership checks** | A `Propietario` can only access their own `inmuebles`, `servicios_inmueble`, and `facturas`. |
| **Self-protection** | An authenticated `Administrador` cannot block or deactivate their own account. |

## 8) Coding Conventions

| Item | Rule |
|---|---|
| **Language** | PHP, SQL and user-facing messages/comments in Spanish where appropriate. |
| **Naming (Classes)** | PascalCase: `UsuarioController`, `UsuarioService`, `UsuarioRepository`, `UsuarioModel`, `UsuarioEntity`. |
| **Naming (Methods/Vars)** | camelCase: `findByEmail()`, `$idInmueble`, `$periodo`. |
| **Naming (DB)** | snake_case: `id_rol`, `fecha_vto1`, `domicilio_facturacion`. |
| **Files** | One class per file. Follow PSR-4 via CI4 autoload. |
| **Views** | Use AdminLTE structure. Keep HTML semantic. No inline CSS/JS. Put assets in `public/assets/`. |
| **Routes** | Explicit and grouped. Use meaningful names. Protect with filters. |
| **Migrations** | Use `php spark make:migration` style. Keep them incremental. |
| **Errors/Flash** | Use `session()->setFlashdata()` for feedback. |
| **Formatting** | 4 spaces for indentation. No trailing whitespace. |

## 9) Hito 1 Scope

All user stories from `docs/features-hito1.txt` must be implemented (HU-01 to HU-15) with their acceptance criteria.

## 10) Minor Skills

Use the following minor skills located in their own directories:

| Skill | Purpose |
|---|---|
| **adminlte-view-craft** | Craft clean AdminLTE views (layouts, forms, tables, filters, flash alerts, modals). |
| **ci4-hu-validator** | Map each HU + acceptance criteria to routes/controllers/services/views and generate a checklist. |
| **ci4-migration-builder** | Generate CI4 Migrations and minimal Seeds from the schema. |
| **ci4-module-scaffold** | Scaffold Controller, Service, Repository, Model/Entity, Views and Routes. |
| **ci4-routes-guard** | Structure and protect routes with Filters and RBAC. |
| **ci4-security-audit** | Audit Auth, Sessions, CSRF, RBAC, brute-force and ownership. |
| **ci4-test-suite** | Create lightweight PHPUnit tests for critical Services and validations. |

**Rule:** Follow this major skill strictly. Build on top of CodeIgniter 4's native tooling and the Services/Repositories pattern defined above.
