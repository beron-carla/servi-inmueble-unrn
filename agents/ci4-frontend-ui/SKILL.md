---
name: ci4-frontend-ui
description: Skill for building a consistent frontend layer in ServiInmueble 2026 using CodeIgniter 4 views, AdminLTE 3/4, reusable components, layouts, forms, tables, filters and role-based UI.
---

# CI4 Frontend UI

## 1) Objective

Standardize the entire presentation layer of the `ServiInmueble 2026` project. The goal is to have reusable, maintainable, secure, and consistent views that align with the project's architecture.

## 2) When to Use

- Creating or refactoring views for any module (Auth, Admin, Inmuebles, Tipos de Servicio, Facturas)
- Designing forms, grids/tables, filters, modals, or dashboards
- Creating reusable components (`partials/` or `components/`)
- Adjusting `header/sidebar` based on user roles
- Unifying styles, flash messages, or visual validations

## 3) Principles

- **Server-side first:** Never trust client-side validations. Frontend should only guide the user.
- **Reusable:** Prioritize partials/components over duplicating HTML.
- **No inline code:** Prohibit `style=""`, `onclick=""`, or inline `<script>` tags.
- **Secure:** Use `csrf_field()` on all POST forms. Respect role-based visibility.
- **Consistent:** Always follow the layout and component system defined here.
- **Clear and accessible:** Messages in Spanish, empty states, and field-level validation errors.

## 4) View Structure

All views must follow this structure:

```text
app/Views/
├── layout/
│   ├── main.php      # Base layout (head, body, assets, AdminLTE init)
│   ├── header.php    # Top navbar, user info, logout
│   ├── sidebar.php   # Side menu with role-based logic
│   └── footer.php    # Optional footer
├── partials/
│   ├── alerts.php    # Flash messages (success/error/warning/info)
│   ├── pagination.php
│   └── breadcrumb.php
├── components/
│   ├── card.php      # Reusable card
│   ├── table.php     # Table wrapper with empty state
│   ├── filter-bar.php
│   ├── modal-confirm.php
│   └── badge-state.php
└── {module}/         # Module-specific views (auth/, admin/usuarios/, inmuebles/, etc.)
```

## 5) Using the Layout

**Required** for all authenticated views or views using the AdminLTE structure:

```php
<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
  <!-- Content here -->
<?= $this->endSection() ?>
```

- Minimal public views (login/register) may use a different layout **only** if one already exists. Do not invent a new one without justification.
- Always include `<?= $this->include('partials/alerts') ?>` within content, or ensure `layout/main` renders it.

## 6) Components and Reusability

| Component | Purpose |
|---|---|
| `partials/alerts.php` | Display `session()->getFlashdata('success')`, `session()->getFlashdata('error')`, `session()->getFlashdata('warning')`, `session()->getFlashdata('info')` |
| `components/card.php` | Group forms, filters, or listings |
| `components/table.php` | Tables with headers, body, empty state, and actions |
| `components/filter-bar.php` | Unified GET filters (preserve values with `old()`/`$filters`) |
| `components/modal-confirm.php` | Confirmations for logical/destructive actions (deactivate, block, disable) |
| `components/badge-state.php` | Consistent badges by state (`Activo`, `Inactivo`, `Pendiente`, `Pagada`, `Vencida`, `Bloqueado`, `Baja`, `Pausado`, `En Revisión`) |

**Rule:** If an HTML block is repeated in >= 2 views, it must be converted into a partial/component.

## 7) Forms

| Rule | Details |
|---|---|
| **Method** | Use `POST` for create/edit/update. `GET` only for listing/filtering/searching. |
| **CSRF** | `<?= csrf_field() ?>` is mandatory for all `POST` forms. |
| **Repopulation** | Use `set_value()`, `old()`, or the `$data` array from the controller to repopulate after validation errors. |
| **Validation** | Display field-level errors with `\Config\Services::validation()->getError('field')`. Also support a general error list if present. |
| **Required** | Only mark fields as `required` if they match server-side validation rules. |
| **Selects/Radios** | Preserve correct selection after validation failure. |
| **Buttons** | Use AdminLTE/Bootstrap classes. Clear Spanish labels (`Guardar`, `Cancelar`, `Volver`, `Filtrar`, `Limpiar`). |

## 8) Tables, Filters, and States

| Element | Rule |
|---|---|
| **Tables** | Use classes `table table-bordered table-striped table-hover table-sm` when applicable. Include `<thead>` with clear labels. |
| **Actions** | Only show actions allowed by the current role/session. Use icons + text or icons with `title/tooltip`. |
| **Empty state** | Mandatory. Examples: *"No se encontraron usuarios."*, *"No hay facturas registradas."*, *"No hay inmuebles para mostrar."* |
| **Filters** | Always use `GET`. Must preserve selected values after submission. Group in a `card` with `card-header`. |
| **Pagination** | Use `partials/pagination.php` if pagination is implemented. Preserve filter query strings. |
| **Badges** | Use `components/badge-state.php` to unify colors by state. |
| **Totals (Invoices)** | For HU-15, display cards for `Total Pendiente`, `Total Pagado`, `Total General`. These must be calculated based on applied filters. |

## 9) Assets, CSS, and JS

| Rule | Details |
|---|---|
| **Assets path** | All assets from `public/assets/`. Expected structure: `public/assets/css/`, `public/assets/js/`, `public/assets/adminlte/`, `public/assets/plugins/`, `public/assets/img/`. |
| **No inline** | No inline CSS/JS. Create module-specific files in `public/assets/js/` if custom logic is needed. |
| **Load order** | CSS in `<head>`, JS at the end before `</body>`. |
| **Dependencies** | Use existing dependencies (jQuery, Bootstrap, AdminLTE, FontAwesome). Avoid adding heavy libraries without justification. |
| **Minimal JS** | Use `fetch()` only for small justified actions (confirmations, toggles). Never convert CRUD into an SPA. Prefer classic form submissions. |
| **Selectors** | Use `data-*` attributes for JS hooks. Avoid fragile generic IDs. |

## 10) CodeIgniter 4 Integration

| Element | Usage |
|---|---|
| **URLs** | Use `site_url()`, `base_url()`, or `url_to()` as appropriate. Never hardcode absolute routes. |
| **Session/Flash** | Read via `session()`. Render via `partials/alerts.php`. |
| **Validation** | Integrate with `Services::validation()` to display errors. |
| **Old input** | Use `old('field')` or `set_value()` after redirects with `withInput()`. |
| **Visual authorization** | Hide buttons/links if the role has no permission. **This is UI-only**. Real authorization must always be enforced in Filters/Services. |

## 11) Frontend Checklist

Before considering any view complete, verify:

- [ ] Uses `<?= $this->extend('layout/main') ?>` and `<?= $this->section('content') ?>`
- [ ] Includes `partials/alerts.php` or flash messages are rendered
- [ ] `<?= csrf_field() ?>` present on all `POST` forms
- [ ] Validation errors displayed per field
- [ ] Values repopulated after errors (`old()`/`set_value()`)
- [ ] Empty state present in listings
- [ ] GET filters preserve values
- [ ] Actions visible only based on role
- [ ] No inline CSS/JS
- [ ] Assets loaded from `public/assets/`
- [ ] Clear Spanish button labels
- [ ] Components/partials used when applicable
- [ ] Responsive with AdminLTE/Bootstrap classes
- [ ] Consistent badges for states
- [ ] Confirmation modal used for logical actions (when applicable)

## 12) Relation to Other Skills

| Skill | Relation |
|---|---|
| `adminlte-view-craft` | **Complementary.** Follow both. `adminlte-view-craft` focuses on crafting views with AdminLTE components, while `ci4-frontend-ui` defines the global frontend architecture, structure, and conventions. |
| `ci4-routes-guard` | Ensure views are only accessible through properly protected routes. |
| `ci4-security-audit` | Review CSRF, role-based visibility, and data exposure in views. |
| `ci4-hu-validator` | Verify the UI meets all Acceptance Criteria for the assigned HU. |
| `ci4-module-scaffold` | Coordinate view creation with controllers/services/repositories during scaffolding. |
