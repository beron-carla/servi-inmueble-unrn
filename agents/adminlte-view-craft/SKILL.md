---
name: adminlte-view-craft
description: Skill to create clean, consistent AdminLTE views for ServiInmueble 2026 following CI4 layouts, forms, tables, filters and flash messages.
---

# AdminLTE View Craft

## Goal

Build reusable, responsive AdminLTE views for all modules while keeping HTML semantic, assets separated and fully compatible with CodeIgniter 4's view layout system.

## When to use

- Creating or updating views for any module (Auth, Admin, Inmuebles, Servicios, Facturas)
- Building forms, data tables, filters, dashboards and modals
- Refactoring views to use the shared layout

## Requirements

- Use `<?= $this->extend('layout/main') ?>` and `<?= $this->section('content') ?>`
- Use AdminLTE 3/4 components (cards, tables, forms, alerts, badges, buttons)
- No inline CSS or inline JavaScript
- Load assets from `public/assets/` (`css/`, `js/`, `adminlte/`, `img/`)
- Use `<?= csrf_field() ?>` on all mutating forms
- Display flashdata with `session()->getFlashdata('success')` and `session()->getFlashdata('error')`
- Respect role-based visibility (only show actions allowed for the current role)

## Layout Structure

| File | Purpose |
|---|---|
| `app/Views/layout/main.php` | Base layout (doctype, head, body, scripts, AdminLTE init) |
| `app/Views/layout/header.php` | Top navbar, user info and logout |
| `app/Views/layout/sidebar.php` | Role-based navigation menu |
| `app/Views/partials/alerts.php` | Reusable flash message block |
| `app/Views/partials/pagination.php` | Reusable pagination if used |

## View Guidelines

| Element | Rule |
|---|---|
| **Forms** | Use labels, placeholders where useful, `required` only when matching server rules, and show validation errors from `\Config\Services::validation()->getErrors()`. |
| **Tables** | Use `table table-bordered table-striped table-hover`. Include empty state with a clear message. |
| **Filters** | Group filters in a card with fields for `inmueble`, `servicio`, `estado`, `ciudad`, `tipo`, `propietario`, `date range`. Keep GET based. |
| **Totals/Summaries** | For invoices, display cards for "Pending total", "Paid total", "Grand total" recalculated based on applied filters. |
| **States/Badges** | Use consistent badges for states (`Activo`, `Inactivo`, `Pendiente`, `Pagada`, `Vencida`, `Bloqueado`, `Baja`). |
| **Modals** | Only use modals for confirmations (e.g. deactivate/reactivate). Avoid complex multi-step modals. |
| **Grids/Cards** | For properties, use a card grid with key info and action buttons. |

## Checklist

- [ ] Layout extended correctly
- [ ] CSRF present on POST forms
- [ ] Flash messages rendered
- [ ] Role-based actions hidden when not allowed
- [ ] Validation errors displayed per field
- [ ] Empty states present
- [ ] Filters preserve values
- [ ] Assets loaded correctly
- [ ] No inline styles/scripts
