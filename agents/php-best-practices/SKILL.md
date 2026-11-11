---
name: php-best-practices
description: "Use when writing, modifying, reviewing, or refactoring PHP code. Covers PHP quality, SOLID, PSR-12, architecture, security, database practices, CodeIgniter 4, URL generation, testing, and Git collaboration."
---

# PHP Programming Best Practices

Apply these guidelines when writing, modifying, or reviewing PHP code. Always follow the project's existing conventions and architecture where they are more specific.

## General Principles

- Write clean, readable, maintainable, and reusable code.
- Apply SOLID and DRY where appropriate without overengineering.
- Prefer simple, explicit, predictable behavior.
- Use meaningful names and keep functions and methods focused on one responsibility.
- Follow the existing project's coding conventions and architecture.

## PHP Standards and Code Quality

- Follow PSR-12 and relevant PHP-FIG recommendations.
- Use appropriate visibility modifiers and declare parameter and return types whenever possible.
- Use `declare(strict_types=1);` when compatible with the existing project.
- Prefer modern PHP features supported by the project's PHP version.
- Avoid deprecated functions, unnecessary globals, and poorly structured code.
- Handle errors and exceptions explicitly; never silently ignore failures.
- Prefer early returns when they improve readability and reduce nesting.
- Comment on complex logic or important decisions, not on behavior already clear from the code.

## Architecture and Separation of Concerns

- Keep controllers, models, repositories, services, and views responsible for their established roles.
- Controllers coordinate requests and responses; keep complex business logic in the appropriate service or domain layer.
- Centralize database access in the project's established data-access layer.
- Keep views focused on presentation and avoid business logic in templates.
- Avoid tight coupling between unrelated components.
- Reuse existing project abstractions before introducing new ones; do not add unnecessary files, classes, or layers.

## Security

- Validate and sanitize all user input on the server. Never trust form, URL, cookie, or external data.
- Use prepared statements or the framework's query builder to prevent SQL injection.
- Escape dynamic HTML output to prevent XSS.
- Apply CSRF protection to state-changing requests.
- Hash passwords with a secure algorithm such as Argon2id or bcrypt; never store plaintext passwords.
- Enforce authentication and authorization on the server.
- Never expose passwords, tokens, credentials, or other sensitive configuration.
- Keep environment-specific configuration in environment variables rather than hardcoding credentials.
- Apply least privilege to roles and permissions.

## Database Practices

- Use appropriate primary keys, foreign keys, indexes, and constraints; preserve referential integrity.
- Use transactions when multiple operations must succeed or fail together.
- Avoid unnecessary queries and N+1 query problems; retrieve only the data required.
- Use clear, consistent table and column naming.
- Handle database errors without exposing internal details to users.
- Do not disable foreign-key checks as a substitute for correcting insertion order or data integrity.

## CodeIgniter 4

- Follow the framework's MVC architecture and the project's existing layered conventions.
- Define endpoints in `app/Config/Routes.php`; use named routes where appropriate instead of hardcoding paths.
- Use framework helpers, validation, filters, sessions, migrations, and the query builder where suitable.
- Respect `app.baseURL` and the deployment environment when generating URLs.
- Use framework redirect and response mechanisms consistently.
- Keep configuration and environment-specific values out of business logic.
- Prefer established project patterns over incompatible alternatives.

### Named Routes and URL Generation

`route_to()` returns the URI for a named route; it does not necessarily include the application's base URL. When an absolute application URL is needed, wrap it with `site_url()`:

```php
<a href="<?= site_url(route_to('auth.register')) ?>">Crear una cuenta</a>
```

In a subdirectory deployment, a relative route can resolve outside the application. For example, the intended route may be:

```text
http://localhost/servi-inmueble/public/auth/registro
```

while an unprefixed relative route can resolve as:

```text
http://localhost/auth/registro
```

Apply the same reasoning to forms, redirects, and other generated URLs. Do not blindly wrap every `route_to()` call: determine whether an absolute application URL is required, then verify it against the configured `app.baseURL` and deployment environment.

## Error Handling and Validation

- Validate incoming data before processing it; use framework validation when suitable.
- Give users meaningful errors without exposing sensitive implementation details.
- Handle expected exceptions explicitly and avoid empty `catch` blocks.
- Log relevant technical errors using the project's logging system.
- Do not use error-suppression operators to hide problems.

## Maintainability and Refactoring

- Prefer small, focused changes over unnecessary rewrites.
- Preserve existing functionality unless a behavior change is explicitly required.
- Avoid modifying unrelated files or components.
- Inspect the codebase for reusable solutions before introducing another implementation.
- Refactor duplication when it improves clarity and maintainability.
- Remove unused imports, variables, methods, and dependencies when safe.
- Do not add dependencies without clear technical justification.

## Testing and Verification

- Verify compatibility with the project's PHP and framework versions.
- Test normal cases, edge cases, and expected failures.
- Add or update automated tests when appropriate.
- For relevant changes, verify routes, redirects, form submissions, validation, authentication, and database behavior.
- Run the existing targeted tests and broader suite when appropriate.
- Report only tests and checks that were actually run; identify any manual testing still needed.
- Preserve existing functionality while implementing changes.

## Git and Collaboration

- Inspect Git status before modifying or staging files.
- Preserve other contributors' changes; do not overwrite, discard, or reset work without explicit authorization.
- Avoid broad staging commands such as `git add .` when unrelated or untracked files may exist.
- Keep commits focused and use descriptive messages.
- Do not commit credentials, local configuration, temporary files, or generated dependencies unless explicitly required.

## Working on Existing Code

Before changing code:

1. Inspect the relevant files and understand their responsibilities.
2. Identify existing conventions, architecture, and dependencies.
3. Determine how the change affects other parts of the application.
4. Choose the smallest safe change that fully solves the requirement.
5. Preserve compatibility with the current project architecture.

After changing code:

1. Explain what changed and why.
2. Identify relevant side effects or files that need attention.
3. State which tests or verification steps were performed.
4. Clearly identify anything that still requires manual testing.

## General Rule

Do not prioritize a temporary fix over correctness, security, maintainability, and compatibility with the existing project. When uncertain, inspect the current implementation and explain the reasonable options before making assumptions or introducing architectural changes.
