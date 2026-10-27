---
name: ci4-migration-builder
description: Skill to generate CodeIgniter 4 Migrations and minimal Seeds from the ServiInmueble schema, ensuring correct constraints, FKs, soft deletes and indexes.
---

# CI4 Migration Builder

## Goal

Translate the logical schema to clean CI4 migrations. Use `app/Database/Migrations/` and provide minimal seeds for testing.

## When to use

- Initial schema setup
- Updating schema to match new requirements
- Preparing DB for local testing with `php spark migrate`

## Inputs

- `agents/servi-inmueble-ci4/SKILL.md` (Database Design section)
- `docs/enunciado.txt`
- Any existing `sql/schema.sql`

## Migration Order

Create in this order to respect foreign keys:

1. `CreateRolesTable`
2. `CreateUsuariosTable`
3. `CreateTiposServicioTable`
4. `CreateInmueblesTable`
5. `CreateServiciosInmuebleTable`
6. `CreateFacturasTable`

## Column Standards

| Type | Usage |
|---|---|
| `INT UNSIGNED AUTO_INCREMENT` | Primary keys |
| `VARCHAR` | Short strings (names, emails, codes) |
| `DECIMAL(10,2)` | Amounts and surface |
| `DATE`/`DATETIME` | Dates and audit fields |
| `TINYINT(1)` | Flags like `activo` |
| `ENUM` or `VARCHAR` with validation | For states/categories/units |
| `TEXT` | Longer notes if needed |

## Required Constraints

- `ENGINE = InnoDB`
- `DEFAULT CHARSET = utf8mb4`, `COLLATE = utf8mb4_unicode_ci`
- `UNIQUE KEY` for `email`, `roles.nombre`, `tipos_servicio.nombre`
- `FOREIGN KEY` with `ON UPDATE CASCADE`, choose `ON DELETE RESTRICT` or `NO ACTION` to avoid accidental loss of history
- Soft delete: add `deleted_at` (DATETIME NULL) to `usuarios` and `inmuebles`
- Audit: `created_at` (DATETIME NULL DEFAULT CURRENT_TIMESTAMP), `updated_at` (DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)

## Indexes

Add indexes on: `usuarios.id_rol`, `usuarios.estado`, `inmuebles.id_usuario_propietario`, `inmuebles.estado`, `servicios_inmueble.id_inmueble`, `servicios_inmueble.id_tipo_servicio`, `facturas.id_servicio_inmueble`, `facturas.periodo`.

## Seeds

Create minimal seeds in `app/Database/Seeds/`:

| Seed | Content |
|---|---|
| `RoleSeeder` | Insert `Administrador` and `Propietario` |
| `TipoServicioSeeder` | Optional catalog for quick testing |
| `UserAdminSeeder` | Create a default admin with hashed password (document credentials clearly) |

> Never commit real production credentials. Use obvious test values.

## Commands

```bash
php spark make:migration CreateRolesTable
php spark make:migration CreateUsuariosTable
php spark migrate
php spark db:seed RoleSeeder
php spark db:seed UserAdminSeeder
```

## Checklist

- [ ] Migrations created in correct order
- [ ] All FKs and UNIQUEs defined
- [ ] Soft deletes and audit fields present
- [ ] Indexes added
- [ ] Seeds created and tested
- [ ] `php spark migrate` runs without errors
- [ ] `php spark migrate:rollback` works as expected
