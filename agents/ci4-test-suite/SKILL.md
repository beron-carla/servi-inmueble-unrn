---
name: ci4-test-suite
description: Skill to create lightweight PHPUnit tests for critical business logic in Services and key validations for ServiInmueble using CI4 testing tools.
---

# CI4 Test Suite

## Goal

Add targeted tests for high-value logic (validations, uniqueness, state transitions, ownership) without over-testing views.

## When to use

- Testing complex Service methods
- Validating critical rules (password policy, invoice readings, service uniqueness)
- Regression checks before demo

## Setup

CI4 includes PHPUnit. Tests go in `tests/` directory.

```bash
vendor/bin/phpunit
# or
php spark test
```

## Test Types

| Type | Location | Purpose |
|---|---|---|
| **Unit Test** | `tests/unit/Services/` | Test Service methods with mocked Repositories |
| **Feature Test** | `tests/feature/` | Test HTTP flows (login, protected routes, forms) |
| **Database Test** | `tests/database/` | Test Migrations/Models with test DB if configured |

## Priority Targets

| Module | What to test |
|---|---|
| **Auth** | Password policy, email format, generic errors, blocked user |
| **Usuarios** | Uniqueness, self-delete prevention, state changes |
| **TiposServicio** | Uniqueness, category/unit validation, cannot delete if linked |
| **Inmuebles** | Ownership, state transitions, soft delete |
| **ServiciosInmueble** | Unique active per property+type, cannot invoice if not active |
| **Facturas** | Readings (actual >= anterior), period uniqueness, due date coherence, totals logic |

## Example Unit Test

```php
<?php

namespace Tests\Unit\Services;

use App\Services\UsuarioService;
use CodeIgniter\Test\CIUnitTestCase;

class UsuarioServiceTest extends CIUnitTestCase
{
    public function testPasswordPolicyRequiresLetterAndNumber(): void
    {
        $service = new UsuarioService();
        $this->assertFalse($service->isValidPassword('abcdefgh'));
        $this->assertFalse($service->isValidPassword('12345678'));
        $this->assertTrue($service->isValidPassword('Secreta123'));
    }
}
```

## Example Feature Test

```php
<?php

namespace Tests\Feature;

use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\CIUnitTestCase;

class AuthTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testGuestCannotAccessDashboard(): void
    {
        $result = $this->get('/dashboard');
        $result->assertRedirectTo('/auth/login');
    }
}
```

## Guidelines

- Keep tests focused and fast
- Don't test CI4 internals
- Mock external dependencies
- Use descriptive test names
- Clear test DB or use transactions if hitting DB
- Avoid testing presentation details

## Checklist

- [ ] PHPUnit configured
- [ ] Critical Services have unit tests
- [ ] Key auth flows have feature tests
- [ ] Tests pass locally
- [ ] No tests hitting real production data
