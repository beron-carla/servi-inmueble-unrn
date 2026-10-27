---
name: ci4-security-audit
description: Skill to audit authentication, authorization, sessions, CSRF, brute-force, validation and ownership against ServiInmueble security rules.
---

# CI4 Security Audit

## Goal

Perform a systematic security review to ensure compliance with the ServiInmueble CI4 security rules.

## When to use

- Before merging module
- Prior to demo
- After auth/role changes

## Audit Checklist

### 1) Authentication

- [ ] Login uses `password_verify()` against `password_hash()`
- [ ] Passwords never stored in plain text
- [ ] Session ID regenerated on login: `session()->regenerate(true)`
- [ ] Logout destroys session: `session()->destroy()` and clears cookie
- [ ] Generic error on failed login: "Email o contraseña incorrectos"
- [ ] Blocked/Baja users cannot login
- [ ] Brute force: max 5 attempts, 15 min cooldown implemented

### 2) Authorization (RBAC)

- [ ] `AuthFilter` applied to protected routes
- [ ] `RoleFilter` applied to admin routes
- [ ] Unauthorized returns 403 with "No tiene permisos para esta acción"
- [ ] Admin cannot self-delete/block
- [ ] Ownership enforced for `Propietario` (inmuebles/servicios/facturas)

### 3) Sessions & CSRF

- [ ] CI4 Sessions configured properly
- [ ] Session regenerated on login
- [ ] `<?= csrf_field() ?>` on all POST forms
- [ ] CSRF validation active
- [ ] PRG used after mutations

### 4) Input & Validation

- [ ] All forms validated server-side with CI4 Validation
- [ ] Email uniqueness (case-insensitive) enforced
- [ ] Password policy: >= 8 chars, at least 1 letter and 1 number
- [ ] Numeric/date/enum validations applied
- [ ] Trimming applied where appropriate
- [ ] No reliance on client-side only validation

### 5) Data Integrity

- [ ] Soft deletes used where required
- [ ] No physical deletes of invoices/services/history
- [ ] State transitions validated
- [ ] Unique constraints enforced (email, periodo, active service)
- [ ] FKs respected

### 6) Routes & Exposure

- [ ] No admin actions exposed without filters
- [ ] Direct URL access blocked
- [ ] 404/403 handled
- [ ] Public routes limited to auth

## Testing Scenarios

| Test | Expected |
|---|---|
| **Access protected without session** | Redirect to `/auth/login` |
| **Propietario hits admin URL** | 403 with correct message |
| **5 failed logins** | Cooldown applied |
| **Blocked user login** | Blocked, no session |
| **Logout then back** | Redirect to login |
| **Submit form without CSRF** | Rejected |
| **Modify other owner's property** | Forbidden |

## Report Template

```markdown
# Security Audit Report

## Summary
- Critical: X
- High: X
- Medium: X
- Low: X

## Findings
| ID | Severity | Area | Issue | Recommendation | Status |
|---|---|---|---|---|---|

## Conclusion
[Overall assessment]
```

## Checklist

- [ ] All items reviewed
- [ ] Findings documented
- [ ] Fixes applied
- [ ] Re-tested critical flows
