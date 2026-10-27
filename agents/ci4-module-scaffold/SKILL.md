---
name: ci4-module-scaffold
description: Skill to scaffold a complete CI4 module following the ServiInmueble pattern (Controller, Service, Repository, Model/Entity, Views, Routes) with AdminLTE integration.
---

# CI4 Module Scaffold

## Goal

Generate a consistent, production-aligned module structure to enforce the Services/Repositories architecture.

## When to use

- Creating a new module (e.g. `Usuarios`, `Inmuebles`, `TiposServicio`, `Facturas`)
- Refactoring an existing module to match the pattern
- Bootstrapping CRUDs for Hito 1

## Module Naming

| Entity | Suggested Names |
|---|---|
| **Usuarios** | Controller: `Admin/Usuarios`, Service: `UsuarioService`, Repository: `UsuarioRepository`, Model: `UsuarioModel`, Entity: `Usuario` |
| **Tipos de Servicio** | Controller: `Admin/TiposServicio`, Service: `TipoServicioService`, Repository: `TipoServicioRepository`, Model: `TipoServicioModel`, Entity: `TipoServicio` |
| **Inmuebles** | Controller: `Inmuebles`, Service: `InmuebleService`, Repository: `InmuebleRepository`, Model: `InmuebleModel`, Entity: `Inmueble` |
| **Servicios Inmueble** | Controller: `ServiciosInmueble`, Service: `ServicioInmuebleService`, Repository: `ServicioInmuebleRepository`, Model: `ServicioInmuebleModel`, Entity: `ServicioInmueble` |
| **Facturas** | Controller: `Facturas`, Service: `FacturaService`, Repository: `FacturaRepository`, Model: `FacturaModel`, Entity: `Factura` |
| **Auth** | Controller: `Auth/Auth`, Service: `AuthService`, Repository: `UsuarioRepository`, Model: `UsuarioModel`, Entity: `Usuario` |

## File Generation Plan

| Layer | Path | Notes |
|---|---|---|
| **Controller** | `app/Controllers/.../XController.php` | Extend `BaseController`. Only HTTP logic. Call Service. PRG on POST. |
| **Service** | `app/Services/XService.php` | Business rules, validations, ownership, state transitions. Inject Repository. |
| **Repository** | `app/Repositories/XRepository.php` | Data access. Inject Model. Use Query Builder/Model methods. |
| **Model** | `app/Models/XModel.php` | Table, primary key, allowed fields, return type, use timestamps if applicable. |
| **Entity** | `app/Entities/X.php` | Optional but recommended for casting. |
| **Views** | `app/Views/.../` | `index.php`, `create.php`, `edit.php`, `show.php` as needed with AdminLTE. |
| **Routes** | `app/Config/Routes.php` | Grouped and filtered. |

## Scaffolding Rules

- **Constructor injection**: Instantiate Service in Controller. Service instantiates Repository. Repository instantiates Model.
- **Validation**: Use CI4 Validation in Controller or Service. Prefer Service for business-level validation.
- **Flash messages**: Set on redirects.
- **Ownership**: Enforce in Service for `Propietario` scoped modules.
- **Soft delete**: Use `deleted_at` logic via Model or set `estado = 'Baja'/'Inactivo'`.
- **States**: Validate allowed transitions.
- **CSRF**: Always include in forms.

## Minimal Template Outline

```php
// app/Services/ExampleService.php
<?php

namespace App\Services;

use App\Repositories\ExampleRepository;

class ExampleService
{
    protected ExampleRepository $repo;

    public function __construct()
    {
        $this->repo = new ExampleRepository();
    }

    public function create(array $data): array
    {
        // business rules here
        return $this->repo->create($data);
    }
}
```

```php
// app/Repositories/ExampleRepository.php
<?php

namespace App\Repositories;

use App\Models\ExampleModel;

class ExampleRepository
{
    protected ExampleModel $model;

    public function __construct()
    {
        $this->model = new ExampleModel();
    }

    public function findAll()
    {
        return $this->model->findAll();
    }
}
```

```php
// app/Controllers/ExampleController.php
<?php

namespace App\Controllers;

use App\Services\ExampleService;

class ExampleController extends BaseController
{
    protected ExampleService $service;

    public function __construct()
    {
        $this->service = new ExampleService();
    }

    public function index()
    {
        // call service, render view
    }
}
```

## Checklist

- [ ] All layers created
- [ ] Routes added and protected
- [ ] Views use AdminLTE layout
- [ ] Validation matches HU
- [ ] Ownership/role checks applied
- [ ] Soft delete/state logic implemented
- [ ] No cross-layer violations
