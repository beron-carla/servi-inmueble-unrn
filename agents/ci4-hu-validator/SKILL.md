---
name: ci4-hu-validator
description: Skill to map Hito 1 User Stories and Acceptance Criteria to the actual CI4 implementation and generate a traceability checklist.
---

# CI4 HU Validator

## Goal

Ensure full traceability between `docs/features-hito1.txt` (HU-01 to HU-15) and the implemented codebase. Detect missing routes, controllers, services, repositories, validations, views or filters.

## When to use

- After scaffolding a module
- Before a demo/review
- To run a final Hito 1 coverage check

## Inputs

- `docs/features-hito1.txt`
- `app/Config/Routes.php`
- `app/Controllers/`
- `app/Services/`
- `app/Repositories/`
- `app/Views/`
- `app/Filters/`

## Validation Scope

| Area | Check |
|---|---|
| **Routes** | Every action from the HU has a corresponding route (grouped and protected). |
| **Authorization** | Admin-only routes have `RoleFilter`. Ownership enforced for `Propietario`. |
| **Controllers** | Only HTTP logic present. Delegate to Services. Use PRG on mutations. |
| **Services** | Contain business rules, uniqueness, state transitions, ownership and validations. |
| **Repositories** | Data access only. Use Models/Query Builder with bindings. |
| **Validation** | Server-side rules match each Scenario Outline and edge cases. |
| **Views** | Forms, lists, filters, totals and empty states exist as specified. |
| **Security** | Auth, CSRF, brute-force, states and self-protection respected. |

## Traceability Matrix

Generate a table in this format:

| HU | Feature | Routes | Controller | Service | Repository | Filter/Auth | Views | Scenarios | Status (OK/Partial/Missing) | Notes |
|---|---|---|---|---|---|---|---|---|---|---|

## Per-HU Quick Checks

| HU | Must verify |
|---|---|
| **HU-01-03** | Registration defaults to `Propietario`, login regenerates session, logout destroys session, blocked/banned handled. |
| **HU-04-06** | Only `Administrador` can access `/admin/usuarios/*`, self-delete blocked, soft delete to `Baja`, filters/search. |
| **HU-07-09** | Catalog CRUD restricted to admin, uniqueness, category/unit validation, deactivate if linked. |
| **HU-10-12** | Ownership enforced, states validated, soft delete to `Inactivo`, filters by city/type/state/owner. |
| **HU-13** | Contract fields required, unique active service per property-type, states block invoicing, history preserved. |
| **HU-14-15** | Readings validation, period uniqueness, due date coherence, filters, pending/paid/grand totals recalculated. |

## Output

- `docs/hito1-traceability.md` with the matrix
- `docs/hito1-checklist.md` with actionable fixes

## Checklist

- [ ] All 15 HUs reviewed
- [ ] All acceptance criteria mapped
- [ ] Missing items listed with concrete fixes
- [ ] Filters and ownership verified
- [ ] Totals logic validated
